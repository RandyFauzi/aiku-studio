<?php

namespace App\Mcp\Tools;

use App\Services\Mcp\McpAccess;
use App\Services\Mcp\McpReadQueries;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;

class ListAttendancesTool extends ReadTool
{
    protected const ABILITY = 'attendance';

    protected string $name = 'aiku_list_attendances';

    protected string $title = 'Daftar Absensi Kru';

    protected string $description = 'Menampilkan riwayat absensi (tanpa koordinat GPS mentah) dalam rentang tanggal. Kontributor hanya melihat absensi miliknya sendiri.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Tanggal awal YYYY-MM-DD (default 14 hari terakhir).')->nullable(),
            'end_date' => $schema->string()->description('Tanggal akhir YYYY-MM-DD (default hari ini).')->nullable(),
            'contributor_id' => $schema->integer()->description('Filter ID kontributor (khusus admin).')->nullable(),
            'method' => $schema->string()->enum(['gps', 'wifi', 'qr'])->description('Filter metode check-in.')->nullable(),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maksimal baris yang dikembalikan (1-100).')->nullable(),
        ];
    }

    protected function dataSchema(JsonSchema $schema): array
    {
        return [
            'range' => $schema->object()->required(),
            'total_matched' => $schema->integer()->required(),
            'returned' => $schema->integer()->required(),
            'items' => $schema->array()->required(),
        ];
    }

    protected function execute(Request $request, McpAccess $access, McpReadQueries $queries): array
    {
        return $queries->listAttendances(
            $access->currentUser(),
            $request->string('start_date')->toString() ?: null,
            $request->string('end_date')->toString() ?: null,
            $request->has('contributor_id') ? $request->integer('contributor_id') : null,
            $request->string('method')->toString() ?: null,
            $request->has('limit') ? $request->integer('limit') : null,
        );
    }
}
