<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class McpUsageGuideResource extends Resource
{
    protected string $uri = 'docs://aiku/mcp-usage-guide';

    protected string $name = 'aiku-mcp-usage-guide';

    protected string $title = 'Panduan Penggunaan AIKu MCP';

    protected string $description = 'Ringkasan alur kerja yang disarankan untuk menganalisis operasional, absensi, recording, dan payroll di AIKu Studio.';

    protected string $mimeType = 'text/markdown';

    public function handle(Request $request): Response
    {
        return Response::text(<<<'MD'
# Panduan Penggunaan AIKu MCP

1. Panggil `aiku_get_context` terlebih dahulu untuk mengetahui peran pengguna, timezone, dan periode payroll aktif.
2. Untuk analisis periode payroll, gunakan `aiku_get_payroll_period` untuk mendapatkan `startDate`, `endDate`, dan `label`.
3. Gunakan tool ringkasan (`*_summary`) sebelum menarik daftar detail (`*_list_*`) agar konteks tetap ringkas dan cepat.
4. Seluruh tool bersifat **read-only**. Koordinat GPS mentah, nomor rekening penuh, dan kredensial akun perangkat disensor otomatis demi keamanan.
MD);
    }
}
