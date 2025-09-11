<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FlushSpool extends Command
{
    protected $signature = 'messages:flush-spool';
    protected $description = 'Flush file spool into MySQL in batches';

    public function handle(): int
    {
        $lock = Cache::lock('flush-spool', 300);
        if (!$lock->get()) {
            $this->info('Another flush is in progress.');
            return self::SUCCESS;
        }
        try {
            $base = storage_path('app/messages');
            @mkdir($base, 0775, true);
            $spool = $base . '/spool.ndjson';
            $offsetFile = $base . '/spool.offset';
            if (!is_file($spool)) { $this->info('No spool yet.'); return self::SUCCESS; }

            $offset = 0;
            if (is_file($offsetFile)) {
                $raw = @file_get_contents($offsetFile);
                if ($raw !== false) $offset = (int)trim($raw);
            }
            $fh = @fopen($spool, 'rb');
            if (!$fh) { $this->error('Cannot open spool'); return self::FAILURE; }

            if ($offset > 0) fseek($fh, $offset, SEEK_SET);
            $rows = [];
            $lastPos = $offset;
            while (!feof($fh)) {
                $line = fgets($fh);
                if ($line === false) break;
                $lastPos = ftell($fh);
                $line = trim($line);
                if ($line === '') continue;
                $data = json_decode($line, true);
                if (!is_array($data)) continue;
                $rows[] = $data;
                if (count($rows) >= 1000) break; // safety cap per run
            }
            fclose($fh);

            if (empty($rows)) { $this->info('No new rows.'); return self::SUCCESS; }

            // Prepare insert chunks
            $chunks = array_chunk($rows, 100);
            DB::beginTransaction();
            try {
                foreach ($chunks as $chunk) {
                    $insert = [];
                    foreach ($chunk as $r) {
                        $insert[] = [
                            'id'        => (int)($r['id'] ?? 0),
                            'handle'    => $r['handle'] ?? null,
                            'content'   => $r['content'] ?? '',
                            'device_id' => $r['device_id'] ?? null,
                            'ip_hmac'   => $r['ip_hmac'] ?? null,
                            'created_at'=> $r['ts'] ?? now()->toDateTimeString(),
                            'updated_at'=> $r['ts'] ?? now()->toDateTimeString(),
                        ];
                    }
                    DB::table('messages')->upsert($insert, ['id'], ['handle','content','device_id','ip_hmac','created_at','updated_at']);
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('DB flush failed: ' . $e->getMessage());
                return self::FAILURE;
            }

            // Persist new offset
            @file_put_contents($offsetFile, (string)$lastPos, LOCK_EX);
            $this->info('Flushed ' . count($rows) . ' rows; offset=' . $lastPos);
            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }
}
