<?php

namespace App\Console\Commands;

use App\Models\Contributor;
use App\Services\ContributorAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncContributorLogins extends Command
{
    protected $signature = 'contributors:sync-logins';

    protected $description = 'Create contributor accounts using registered emails without resetting existing passwords';

    public function handle(ContributorAccess $access): int
    {
        $count = 0;
        DB::transaction(function () use ($access, &$count) {
            foreach (Contributor::all() as $person) {
                if ($access->sync($person)) {
                    $count++;
                }
            }
        });
        $this->info($count.' contributor accounts synchronized. Existing passwords retained.');

        return self::SUCCESS;
    }
}
