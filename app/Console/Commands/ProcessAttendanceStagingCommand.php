<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessAttendanceStaging;

class ProcessAttendanceStagingCommand extends Command
{
    protected $signature = 'attendance:process-staging';

    protected $description = 'Process pending attendance staging records into the attendances table';

    public function handle(): int
    {
        $count = app(ProcessAttendanceStaging::class)->handle();

        $this->info("Processed {$count} staging row(s).");

        return self::SUCCESS;
    }
}
