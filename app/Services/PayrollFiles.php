<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class PayrollFiles
{
    public function evidencePath(string $url): ?string
    {
        if (! preg_match('#^/evidence_images/([A-Za-z0-9_./-]+)$#D', $url, $m) || str_contains($m[1], '..')) {
            return null;
        }

        return 'payroll/evidence_images/'.$m[1];
    }

    public function imageData(string $url): ?string
    {
        $path = $this->evidencePath($url);
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }
        $mime = Storage::disk('local')->mimeType($path);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }

    public function evidenceUrls(array $evidence): array
    {
        return collect($evidence)->flatMap(fn ($day) => $day['image_urls'] ?? (empty($day['image_url']) ? [] : [$day['image_url']]))->filter()->unique()->values()->all();
    }
}
