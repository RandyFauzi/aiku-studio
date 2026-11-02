<?php

namespace App\Console\Commands;

use App\Services\AtlasSync;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class AtlasTick extends Command
{
    protected $signature = 'atlas:tick';

    protected $description = 'Process one bounded Atlas synchronization page for shared-hosting cron';

    public function handle(AtlasSync $sync): int
    {
        $auto = $sync->auto();
        if ($auto['enabled'] && ! ($sync->state()['isRunning'] ?? false) && (! $auto['nextRun'] || now()->gte(CarbonImmutable::parse($auto['nextRun'])))) {
            try {
                $sync->request();
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }
        $started = microtime(true);
        for ($page = 0; $page < 3; $page++) {
            $sync->tick();
            if (! ($sync->state()['isRunning'] ?? false) || microtime(true) - $started > 25) {
                break;
            }
        }
        $state = $sync->state();
        if (($state['stage'] ?? '') === 'failed') {
            $this->error($state['message']);

            return self::FAILURE;
        }
        if ($state['isRunning'] ?? false) {
            $this->line($state['message']);
        }

        return self::SUCCESS;
    }
}
