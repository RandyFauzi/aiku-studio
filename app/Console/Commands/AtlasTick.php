<?php

namespace App\Console\Commands;

use App\Services\RecordingSync;
use Illuminate\Console\Command;

class AtlasTick extends Command
{
    protected $signature = 'atlas:tick';

    protected $description = 'Process the shared Atlas and 1618 synchronization schedule';

    public function handle(RecordingSync $sync): int
    {
        $sync->tick();
        $state = $sync->state();
        $this->line($state['message']);

        return ($state['stage'] ?? '') === 'error' ? self::FAILURE : self::SUCCESS;
    }
}
