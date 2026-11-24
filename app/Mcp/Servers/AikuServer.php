<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\DailyBriefingPrompt;
use App\Mcp\Prompts\PayrollReconciliationPrompt;
use App\Mcp\Resources\McpUsageGuideResource;
use App\Mcp\Resources\PayrollRulesResource;
use App\Mcp\Resources\PhonesAndRolesResource;
use App\Mcp\Tools\CalculateLivePayrollTool;
use App\Mcp\Tools\GetAttendanceSummaryTool;
use App\Mcp\Tools\GetContextTool;
use App\Mcp\Tools\GetContributorPerformanceTool;
use App\Mcp\Tools\GetDailyReportSummaryTool;
use App\Mcp\Tools\GetDataAuditSummaryTool;
use App\Mcp\Tools\GetMyPayslipTool;
use App\Mcp\Tools\GetPayrollPeriodTool;
use App\Mcp\Tools\GetRecordingSummaryTool;
use App\Mcp\Tools\GetShiftScheduleTool;
use App\Mcp\Tools\GetStreakSummaryTool;
use App\Mcp\Tools\GetSyncHealthTool;
use App\Mcp\Tools\ListAdminNotesTool;
use App\Mcp\Tools\ListAttendancesTool;
use App\Mcp\Tools\ListContributorsTool;
use App\Mcp\Tools\ListDailyReportsTool;
use App\Mcp\Tools\ListMcpAuditLogsTool;
use App\Mcp\Tools\ListPayrollStatusesTool;
use App\Mcp\Tools\ListPayslipsTool;
use App\Mcp\Tools\ListRecordingsTool;
use App\Mcp\Tools\ListStudioDevicesTool;
use App\Mcp\Tools\ListTaskTranslationsTool;
use Laravel\Mcp\Server;

class AikuServer extends Server
{
    protected string $name = 'AIKu Studio MCP Server';

    protected string $version = '1.0.0';

    public int $defaultPaginationLength = 50;

    public int $maxPaginationLength = 50;

    protected string $instructions = <<<'MARKDOWN'
        Anda terhubung ke sistem AIKu Studio (operasional, absensi, jadwal shift, laporan kerja harian, rekaman studio, dan payroll).
        - Semua informasi bersifat hanya-baca (read-only).
        - Gunakan timezone Asia/Makassar (WITA) untuk seluruh tanggal kerja dan periode payroll.
        - Panggil `aiku_get_context` lebih dulu bila belum mengetahui peran atau periode aktif pengguna.
        - Jika suatu data tidak tersedia untuk peran pengguna, jelaskan dengan sopan dalam bahasa bisnis tanpa menyebut istilah teknis internal (MCP, token, schema, config, atau role policy).
    MARKDOWN;

    protected array $tools = [
        GetContextTool::class,
        GetPayrollPeriodTool::class,
        ListAttendancesTool::class,
        GetAttendanceSummaryTool::class,
        GetShiftScheduleTool::class,
        ListDailyReportsTool::class,
        GetDailyReportSummaryTool::class,
        ListRecordingsTool::class,
        GetRecordingSummaryTool::class,
        GetStreakSummaryTool::class,
        ListStudioDevicesTool::class,
        GetMyPayslipTool::class,
        ListPayslipsTool::class,
        ListPayrollStatusesTool::class,
        CalculateLivePayrollTool::class,
        ListContributorsTool::class,
        GetContributorPerformanceTool::class,
        ListAdminNotesTool::class,
        ListTaskTranslationsTool::class,
        GetSyncHealthTool::class,
        GetDataAuditSummaryTool::class,
        ListMcpAuditLogsTool::class,
    ];

    protected array $resources = [
        PayrollRulesResource::class,
        PhonesAndRolesResource::class,
        McpUsageGuideResource::class,
    ];

    protected array $prompts = [
        DailyBriefingPrompt::class,
        PayrollReconciliationPrompt::class,
    ];
}
