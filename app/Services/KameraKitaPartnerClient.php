<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KameraKitaPartnerClient
{
    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.kamerakita.url'), '/');
        $token = (string) config('services.kamerakita.token');

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Integrasi KameraKita belum dikonfigurasi. Isi KAMERAKITA_API_URL dan KAMERAKITA_API_TOKEN di .env.');
        }

        if (! app()->environment('local', 'testing') && ! str_starts_with(strtolower($baseUrl), 'https://')) {
            throw new RuntimeException('Koneksi integrasi KameraKita wajib menggunakan protokol HTTPS di lingkungan produksi.');
        }

        return Http::baseUrl($baseUrl.'/api/private/studio')
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15);
    }

    private function handleException(RequestException $exception): never
    {
        $response = $exception->response;
        $body = $response?->json();
        $message = null;

        if (is_array($body)) {
            if (! empty($body['message'])) {
                $message = $body['message'];
            }
            if (! empty($body['errors']) && is_array($body['errors'])) {
                $details = collect($body['errors'])->flatten()->filter()->join(' ');
                if ($details !== '') {
                    $message = ($message ? rtrim($message, '.').': ' : '').$details;
                }
            }
        }

        throw new RuntimeException($message ?: $exception->getMessage(), (int) $exception->getCode(), $exception);
    }

    public function parents(): array
    {
        try {
            return $this->request()->get('/parents')->throw()->json('data', []);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function create(array $data): array
    {
        try {
            return $this->request()->post('/partners', $data)->throw()->json('data', []);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function findByEmail(string $email): ?array
    {
        try {
            $response = $this->request()->get('/partners', ['email' => $email])->throw();
            return $response->json('data');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function partner(string $partnerId): array
    {
        try {
            return $this->request()->get('/partners/'.$partnerId)->throw()->json('data', []);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function setStatus(string $partnerId, string $status): array
    {
        try {
            return $this->request()->patch('/partners/'.$partnerId.'/status', ['status' => $status])->throw()->json('data', []);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function update(string $partnerId, array $data): array
    {
        try {
            return $this->request()->put('/partners/'.$partnerId, $data)->throw()->json('data', []);
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function delete(string $partnerId): array
    {
        try {
            return $this->request()->delete('/partners/'.$partnerId)->throw()->json();
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }
}
