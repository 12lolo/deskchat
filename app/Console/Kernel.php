<?php
namespace App\Console;

use App\Models\Message;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Message::where('created_at', '<', now()->subDays(90))->delete();
        })->daily();
    }

    protected function commands(): void {}
}
