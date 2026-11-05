<?php

namespace App\Console\Commands;

use App\Models\DailyWorkReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDataIntegrity extends Command
{
    protected $signature = 'panel:audit-data';

    protected $description = 'Read-only aggregate integrity audit; no names, emails, GPS or payroll values are printed';

    public function handle(): int
    {
        $duplicateGroups = fn (string $table, string $column) => DB::table($table)
            ->whereNotNull($column)->whereRaw("TRIM($column) != ''")
            ->selectRaw("LOWER(TRIM($column)) as normalized_value, COUNT(*) as total")
            ->groupByRaw("LOWER(TRIM($column))")->havingRaw('COUNT(*) > 1')->get()->count();
        $issues = [
            'duplicate_contributor_names' => $duplicateGroups('contributors', 'nama'),
            'duplicate_contributor_emails' => $duplicateGroups('contributors', 'email'),
            'active_users_missing_contributor' => DB::table('users as u')->leftJoin('contributors as c', 'c.id', '=', 'u.contributor_id')
                ->where('u.is_active', true)->where('u.role', 'contributor')->whereNull('c.id')->count(),
            'assignment_id_name_mismatch' => DB::table('studio_recordings as r')->join('contributors as c', 'c.id', '=', 'r.assigned_contributor_id')
                ->where(fn ($q) => $q->whereNull('r.assigned_worker_name')->orWhereColumn('r.assigned_worker_name', '!=', 'c.nama'))->count(),
            'phones_with_multiple_active_accounts' => DB::table('device_accounts')->where('is_active', true)
                ->select('phone_key')->groupBy('phone_key')->havingRaw('COUNT(*) > 1')->get()->count(),
            'payslip_payment_state_mismatch' => DB::table('payslips as p')->join('payroll_statuses as s', fn ($join) => $join
                ->on('s.worker_name', '=', 'p.employee_name')->on('s.period', '=', 'p.period'))
                ->whereColumn('p.status', '!=', 's.status')->count(),
            'daily_report_duration_mismatch' => 0,
        ];
        foreach (['attendances', 'daily_work_reports'] as $table) {
            $issues[$table.'_missing_user'] = DB::table($table.' as r')->leftJoin('users as u', 'u.id', '=', 'r.user_id')->whereNull('u.id')->count();
            $issues[$table.'_owner_identity_mismatch'] = DB::table($table.' as r')->join('users as u', 'u.id', '=', 'r.user_id')
                ->where(fn ($q) => $q->whereNull('u.contributor_id')->orWhereColumn('r.contributor_id', '!=', 'u.contributor_id'))->count();
        }
        DailyWorkReport::query()->chunkById(200, function ($reports) use (&$issues) {
            foreach ($reports as $report) {
                if ($report->phone_durations !== null && (array_sum($report->phone_durations) !== $report->total_duration_minutes
                    || array_diff(array_keys($report->phone_durations), array_keys(DailyWorkReport::REPORT_PHONES)))) {
                    $issues['daily_report_duration_mismatch']++;
                }
            }
        });
        $this->line(json_encode(['read_only' => true, 'issues' => $issues, 'issue_count' => array_sum($issues)], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        return array_sum($issues) ? self::FAILURE : self::SUCCESS;
    }
}
