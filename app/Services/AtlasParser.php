<?php

namespace App\Services;

class AtlasParser
{
    public function payload(string $html): string
    {
        preg_match_all('#<script\b[^>]*>(.*?)</script>#si', $html, $scripts);
        $parts = '';
        foreach ($scripts[1] as $script) {
            if (preg_match('/^\s*self\.__next_f\.push\((.*)\)\s*;?\s*$/s', $script, $m)) {
                $items = json_decode($m[1], true);
                if (is_array($items)) {
                    foreach ($items as $part) {
                        if (is_string($part)) {
                            $parts .= $part;
                        }
                    }
                }
            }
        }

        return $parts;
    }

    public function recordings(string $html): array
    {
        foreach (preg_split('/\r?\n/', $this->payload($html)) as $line) {
            $colon = strpos($line, ':');
            if ($colon === false) {
                continue;
            }
            $decoded = json_decode(substr($line, $colon + 1), true);
            if (is_array($decoded) && ($found = $this->findRecordings($decoded)) !== null) {
                return $found;
            }
        }
        throw new \RuntimeException('Data Atlas tidak dikenali. Sesi mungkin berakhir atau format Atlas berubah.');
    }

    private function findRecordings(array $data): ?array
    {
        if (isset($data['recordings']) && is_array($data['recordings'])) {
            return $data;
        }
        foreach ($data as $child) {
            if (is_array($child) && ($found = $this->findRecordings($child)) !== null) {
                return $found;
            }
        }

        return null;
    }

    public function participants(string $html): array
    {
        $result = [];
        $payload = $this->payload($html);
        // Decode frame JSON so nested participant metadata does not break email/ID discovery.
        foreach (preg_split('/\r?\n/', $payload) as $line) {
            $colon = strpos($line, ':');
            if ($colon !== false) {
                $decoded = json_decode(substr($line, $colon + 1), true);
                if (is_array($decoded)) {
                    $this->collectParticipants($decoded, $result);
                }
            }
        }
        foreach (['/\{[^{}]*?"email"\s*:\s*"([^"]+)"[^{}]*?"id"\s*:\s*"([a-f0-9-]{36})"[^{}]*?\}/',
            '/\{[^{}]*?"id"\s*:\s*"([a-f0-9-]{36})"[^{}]*?"email"\s*:\s*"([^"]+)"[^{}]*?\}/'] as $index => $pattern) {
            preg_match_all($pattern, $payload, $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $result[strtolower(trim($m[$index === 0 ? 1 : 2]))] = $m[$index === 0 ? 2 : 1];
            }
        }

        return $result;
    }

    private function collectParticipants(array $data, array &$result): void
    {
        if (isset($data['email'], $data['id']) && is_string($data['email']) && is_string($data['id'])
            && filter_var($data['email'], FILTER_VALIDATE_EMAIL) && preg_match('/^[a-f0-9-]{36}$/i', $data['id'])) {
            $result[strtolower(trim($data['email']))] = $data['id'];
        }
        foreach ($data as $child) {
            if (is_array($child)) {
                $this->collectParticipants($child, $result);
            }
        }
    }
}
