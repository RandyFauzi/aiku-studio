<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class DailyBriefingPrompt extends Prompt
{
    protected string $name = 'aiku-daily-briefing';

    protected string $title = 'Briefing Operasional Harian';

    protected string $description = 'Menyusun ringkasan operasional harian studio (absensi, jadwal shift, laporan kerja, dan catatan admin yang di-pin).';

    public function arguments(): array
    {
        return [
            new Argument('date', 'Tanggal target YYYY-MM-DD (opsional, default hari ini WITA).', required: false),
        ];
    }

    public function handle(Request $request): Response
    {
        $date = $request->string('date')->toString() ?: now('Asia/Makassar')->toDateString();

        return Response::text(<<<PROMPT
Buat ringkasan operasional AIKu Studio untuk tanggal {$date} (WITA):
1. Panggil `aiku_get_context` untuk memastikan peran dan konteks waktu.
2. Panggil `aiku_list_attendances` dengan `start_date={$date}` dan `end_date={$date}`.
3. Panggil `aiku_list_daily_reports` dengan `start_date={$date}` dan `end_date={$date}`.
4. Jika saya admin, panggil juga `aiku_list_admin_notes` (`pinned_only=true`, `completed=false`) dan `aiku_get_sync_health`.
5. Sajikan ringkasan yang rapi, singkat, dan soroti hal yang perlu ditindaklanjuti.
PROMPT);
    }
}
