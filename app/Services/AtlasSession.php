<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AtlasSession
{
    public const SESSION_PATH = 'payroll/atlas-session.json';
    public const CREDENTIALS_PATH = 'payroll/atlas-credentials.enc';

    public function cookie(): string
    {
        $disk = Storage::disk('local');
        if ($disk->exists(self::SESSION_PATH)) {
            $state = json_decode($disk->get(self::SESSION_PATH), true) ?? [];
            $host = parse_url(config('payroll.atlas_url'), PHP_URL_HOST);
            $cookies = [];
            foreach ($state['cookies'] ?? [] as $cookie) {
                if (! isset($cookie['name'], $cookie['value'])) continue;
                $domain = ltrim($cookie['domain'] ?? '', '.');
                if ($domain !== '' && ($host === $domain || str_ends_with($host, '.'.$domain))
                    && (($cookie['expires'] ?? -1) < 0 || $cookie['expires'] > time())) {
                    $cookies[] = $cookie['name'].'='.$cookie['value'];
                }
            }
            if ($cookies) return implode('; ', $cookies);
        }

        return (string) config('payroll.atlas_cookie', '');
    }

    public function canLogin(): bool
    {
        $credentials = $this->credentials();

        return ! empty($credentials['email']) && ! empty($credentials['password']);
    }

    private function credentials(): array
    {
        if (config('payroll.atlas_username') && config('payroll.atlas_password')) {
            return ['email' => config('payroll.atlas_username'), 'password' => config('payroll.atlas_password')];
        }
        $disk = Storage::disk('local');
        if (! $disk->exists(self::CREDENTIALS_PATH)) return [];
        try {
            return json_decode(Crypt::decryptString($disk->get(self::CREDENTIALS_PATH)), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Kredensial Atlas privat tidak dapat dibaca. Periksa konfigurasi kunci aplikasi.');
        }
    }

    public function refresh(#[\SensitiveParameter] string $rejectedCookie = ''): string
    {
        return Cache::lock('atlas-session-refresh', 90)->block(5, function () use ($rejectedCookie) {
            $current = $this->cookie();
            if ($current !== '' && $current !== $rejectedCookie) return $current;
            if (Cache::has('atlas-login-backoff')) {
                throw new \RuntimeException('Login Atlas sementara ditunda setelah percobaan gagal. Coba lagi setelah satu menit.');
            }
            $credentials = $this->credentials();
            if (empty($credentials['email']) || empty($credentials['password'])) {
                throw new \RuntimeException('Sesi Atlas berakhir dan kredensial login otomatis belum dikonfigurasi di server.');
            }
            try {
                $base = rtrim(config('payroll.atlas_url'), '/');
                if (parse_url($base, PHP_URL_SCHEME) !== 'https') throw new \RuntimeException('HTTPS required');
                $jar = new CookieJar;
                $client = fn () => Http::timeout(config('payroll.atlas_timeout', 12))->connectTimeout(5)
                    ->withOptions(['cookies' => $jar, 'allow_redirects' => false])->withHeaders(['Accept' => 'text/html']);
                $page = $client()->get($base.'/login');
                if (! $page->successful()) throw new \RuntimeException('Login form unavailable');
                $previous = libxml_use_internal_errors(true);
                try {
                    $dom = new DOMDocument;
                    $dom->loadHTML($page->body());
                    $xpath = new DOMXPath($dom);
                    $forms = $xpath->query('//form[.//input[@name="email"] and .//input[@name="password"]]');
                    if (! $forms->length) throw new \RuntimeException('Login form changed');
                    $fields = [];
                    foreach ($xpath->query('.//input[@type="hidden"]', $forms->item(0)) as $input) {
                        if ($input->getAttribute('name') !== '') $fields[$input->getAttribute('name')] = $input->getAttribute('value');
                    }
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous);
                }
                $fields['email'] = $credentials['email'];
                $fields['password'] = $credentials['password'];
                $login = $client()->asMultipart()->withHeaders(['Origin' => $base])->post($base.'/login', $fields);
                if (! in_array($login->status(), [200, 302, 303, 307], true)) throw new \RuntimeException('Login rejected');
                $verify = $client()->get($base.'/recordings');
                if (! $verify->successful()) throw new \RuntimeException('Session rejected');
                app(AtlasParser::class)->recordings($verify->body());
                $cookies = array_map(fn ($c) => ['name' => $c['Name'], 'value' => $c['Value'], 'domain' => $c['Domain'],
                    'expires' => $c['Expires'] ?? -1], $jar->toArray());
                if (! $cookies) throw new \RuntimeException('Session cookie missing');
                $disk = Storage::disk('local');
                $temp = self::SESSION_PATH.'.'.bin2hex(random_bytes(6)).'.tmp';
                try {
                    if (! $disk->put($temp, json_encode(['cookies' => $cookies, 'refreshedAt' => now()->toIso8601String()]))) throw new \RuntimeException('Session write failed');
                    if (! $disk->move($temp, self::SESSION_PATH)) throw new \RuntimeException('Session replace failed');
                    @chmod($disk->path(self::SESSION_PATH), 0600);
                } finally { $disk->delete($temp); }
                Cache::forget('atlas-login-backoff');

                return $this->cookie();
            } catch (\Throwable $e) {
                Cache::put('atlas-login-backoff', true, 60);
                // Never expose login response, password, cookie or the original request exception.
                throw new \RuntimeException('Login ulang Atlas gagal. Periksa kredensial, akses akun, atau perubahan halaman login Atlas. Sinkronisasi 1618 tetap terpisah.');
            }
        });
    }
}
