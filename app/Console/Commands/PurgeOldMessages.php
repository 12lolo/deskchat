<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class PurgeOldMessages extends Command
{
    protected $signature = 'messages:purge {--days=}';
    protected $description = 'Delete messages older than retention days';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('messages.retention_days'));
        if ($days < 1) { $days = 1; }
        $cutoff = now()->subDays($days);
        $count = Message::where('created_at', '<', $cutoff)->delete();
        $this->info("Purged {$count} messages older than {$days} days");
        return self::SUCCESS;
    }
}

