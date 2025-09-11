<?php
namespace App\Console;

use App\Console\Commands\FlushSpool;
use App\Console\Commands\PurgeOldMessages;
use App\Models\Message;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {
    /** @var array<class-string> */
    protected $commands = [
        PurgeOldMessages::class,
        FlushSpool::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Message::where('created_at', '<', now()->subDays(90))->delete();
        })->daily();

        // Flush spool to DB every 10 minutes without overlapping
        $schedule->command('messages:flush-spool')
            ->everyTenMinutes()
            ->withoutOverlapping();
    }

    protected function commands(): void {}
}
