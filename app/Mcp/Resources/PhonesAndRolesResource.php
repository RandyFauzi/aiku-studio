<?php

namespace App\Mcp\Resources;

use App\Models\DailyWorkReport;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class PhonesAndRolesResource extends Resource
{
    protected string $uri = 'schema://aiku/phones-and-roles';

    protected string $name = 'aiku-phones-and-roles';

    protected string $title = 'Daftar Perangkat HP & Matriks Peran';

    protected string $description = 'Referensi kunci perangkat HP standar studio dan batasan akses peran admin vs contributor.';

    protected string $mimeType = 'application/json';

    public function handle(Request $request): Response
    {
        return Response::json([
            'phones' => DailyWorkReport::PHONES,
            'report_phones' => DailyWorkReport::REPORT_PHONES,
            'roles' => [
                'admin' => [
                    'abilities' => config('aiku_mcp.abilities_by_role.admin', []),
                    'scope' => 'Seluruh data studio (GPS mentah & rekening tetap disensor).',
                ],
                'contributor' => [
                    'abilities' => config('aiku_mcp.abilities_by_role.contributor', []),
                    'scope' => 'Hanya data milik akun/kontributor sendiri.',
                ],
            ],
        ]);
    }
}
