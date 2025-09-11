<?php
namespace App\Support;

class FeedStore
{
    private const FEED_LIMIT = 200;

    private function base(): string
    {
        $dir = storage_path('app/messages');
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir;
    }

    private function path(string $name): string
    {
        return $this->base() . DIRECTORY_SEPARATOR . $name;
    }

    public function nextId(): int
    {
        $p = $this->path('seq.txt');
        $fh = @fopen($p, 'c+');
        if (!$fh) throw new \RuntimeException('Cannot open seq.txt');
        try {
            if (!flock($fh, LOCK_EX)) throw new \RuntimeException('Cannot lock seq.txt');
            rewind($fh);
            $raw = stream_get_contents($fh) ?: '0';
            $cur = (int)trim($raw);
            $next = $cur + 1;
            rewind($fh);
            ftruncate($fh, 0);
            fwrite($fh, (string)$next);
            fflush($fh);
            flock($fh, LOCK_UN);
            return $next;
        } finally {
            fclose($fh);
        }
    }

    public function appendMessage(array $msg): void
    {
        // Lock feed operations
        $lock = $this->path('feed.lock');
        $lfh = @fopen($lock, 'c+');
        if (!$lfh) throw new \RuntimeException('Cannot open feed.lock');
        try {
            flock($lfh, LOCK_EX);

            $feedPath = $this->path('feed.json');
            $arr = [];
            if (is_file($feedPath)) {
                $json = @file_get_contents($feedPath);
                if ($json !== false && $json !== '') {
                    $arr = json_decode($json, true) ?: [];
                }
            }
            if (!is_array($arr)) $arr = [];
            $arr[] = $msg;
            if (count($arr) > self::FEED_LIMIT) {
                $arr = array_slice($arr, -self::FEED_LIMIT);
            }
            $tmp = $this->path('feed.tmp');
            $bytes = json_encode(array_values($arr), JSON_UNESCAPED_UNICODE);
            if ($bytes === false) $bytes = '[]';
            // Write atomically
            file_put_contents($tmp, $bytes, LOCK_EX);
            @chmod($tmp, 0664);
            @rename($tmp, $feedPath);
        } finally {
            flock($lfh, LOCK_UN);
            fclose($lfh);
        }
    }

    public function readSince(int $sinceId, int $limit): array
    {
        $feedPath = $this->path('feed.json');
        $messages = [];
        if (is_file($feedPath)) {
            $json = @file_get_contents($feedPath);
            if ($json !== false && $json !== '') {
                $messages = json_decode($json, true) ?: [];
            }
        }
        if (!is_array($messages)) $messages = [];
        $filtered = [];
        foreach ($messages as $m) {
            if (!is_array($m)) continue;
            if (isset($m['id']) && (int)$m['id'] > $sinceId) $filtered[] = $m;
            if (count($filtered) >= $limit) break;
        }
        $lastId = $sinceId;
        if (!empty($filtered)) {
            $ids = array_column($filtered, 'id');
            $lastId = max($ids);
        }
        return [
            'messages' => $filtered,
            'last_id' => (int)$lastId,
        ];
    }

    public function appendSpool(array $msg): void
    {
        $p = $this->path('spool.ndjson');
        $fh = @fopen($p, 'ab');
        if (!$fh) throw new \RuntimeException('Cannot open spool.ndjson');
        try {
            if (!flock($fh, LOCK_EX)) throw new \RuntimeException('Cannot lock spool.ndjson');
            $line = json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n";
            fwrite($fh, $line);
            fflush($fh);
            flock($fh, LOCK_UN);
        } finally {
            fclose($fh);
        }
    }

    public function lastId(): int
    {
        $seq = $this->path('seq.txt');
        if (is_file($seq)) {
            $raw = @file_get_contents($seq);
            if ($raw !== false) {
                $n = (int)trim($raw);
                if ($n > 0) return $n;
            }
        }
        $feedPath = $this->path('feed.json');
        if (is_file($feedPath)) {
            $json = @file_get_contents($feedPath);
            $arr = $json ? json_decode($json, true) : [];
            if (is_array($arr) && !empty($arr)) {
                $last = end($arr);
                return (int)($last['id'] ?? 0);
            }
        }
        return 0;
    }

    public function tailWarmup(): void
    {
        $feedPath = $this->path('feed.json');
        if (is_file($feedPath)) return; // already present
        $spool = $this->path('spool.ndjson');
        if (!is_file($spool)) return;
        $lines = $this->tailLines($spool, self::FEED_LIMIT);
        $msgs = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $row = json_decode($line, true);
            if (!is_array($row)) continue;
            // Reduce to feed shape
            $msgs[] = [
                'id' => (int)($row['id'] ?? 0),
                'handle' => $row['handle'] ?? null,
                'content' => $row['content'] ?? '',
                'ts' => $row['ts'] ?? null,
            ];
        }
        if (!empty($msgs)) {
            $tmp = $this->path('feed.tmp');
            file_put_contents($tmp, json_encode(array_values($msgs), JSON_UNESCAPED_UNICODE), LOCK_EX);
            @rename($tmp, $feedPath);
        }
    }

    private function tailLines(string $file, int $n): array
    {
        $fh = @fopen($file, 'rb');
        if (!$fh) return [];
        $pos = -1; $lines = []; $buffer = '';
        $stat = fstat($fh); $size = $stat['size'] ?? 0;
        while ($n > 0 && $size + $pos >= 0) {
            fseek($fh, $pos, SEEK_END);
            $char = fgetc($fh);
            if ($char === "\n") {
                if ($buffer !== '') { $lines[] = strrev($buffer); $buffer = ''; $n--; if ($n === 0) break; }
            } elseif ($char !== false) {
                $buffer .= $char;
            }
            $pos--;
        }
        if ($buffer !== '' && $n > 0) $lines[] = strrev($buffer);
        fclose($fh);
        return array_reverse($lines);
    }
}
