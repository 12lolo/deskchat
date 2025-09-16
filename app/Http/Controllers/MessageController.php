<?php
namespace App\Http\Controllers;

use App\Support\FeedStore;
use App\Support\IpPrivacy;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $r, FeedStore $store) {
        $sinceId = max(0, (int)$r->query('since_id', 0));
        $limit   = (int)$r->query('limit', 50);
        if ($limit < 1)  $limit = 1;
        if ($limit > 100) $limit = 100;

        $store->tailWarmup();
        $data = $store->readSince($sinceId, $limit);
        return response()->json($data);
    }

    public function store(Request $r, FeedStore $store) {
        $device = trim((string)$r->header('X-Device-Id', ''));
        if ($device === '') {
            return response()->json(['ok'=>false,'error'=>'missing_device_id'], 400);
        }

        // Validation: handle now required (non-empty after trimming) per functional spec
        $v = Validator::make($r->all(), [
            'handle'  => 'required|string|min:1|max:24',
            'content' => 'required|string|min:1|max:280',
        ]);
        if ($v->fails()) {
            return response()->json(['ok'=>false,'error'=>'validation_failed','details'=>$v->errors()->toArray()], 422);
        }

        $handle  = trim((string)$r->input('handle'));
        if ($handle === '') {
            return response()->json(['ok'=>false,'error'=>'validation_failed','details'=>['handle'=>['blank']]], 422);
        }
        $content = (string)$r->input('content');

        // Sanitize: strip HTML, collapse whitespace, trim
        $content = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        if ($content === '') {
            return response()->json(['ok'=>false,'error'=>'validation_failed','details'=>['content'=>['empty_after_sanitization']]], 422);
        }

        if ($this->hasProfanity($content)) {
            return response()->json(['ok'=>false,'error'=>'profanity_blocked'], 422);
        }
        // Ensure storage path exists & writable before attempting writes
        $messagesDir = storage_path('app/messages');
        if (!is_dir($messagesDir)) {
            @mkdir($messagesDir, 0775, true);
        }
        if (!is_dir($messagesDir) || !is_writable($messagesDir)) {
            Log::error('messages storage directory not writable', [
                'path' => $messagesDir,
                'exists' => is_dir($messagesDir),
                'writable' => is_writable($messagesDir),
            ]);
            return response()->json(['ok'=>false,'error'=>'storage_unavailable'], 500);
        }

        try {
            // Build message for feed files
            $msg = [
                'id'      => $store->nextId(),
                'handle'  => $handle,
                'content' => $content,
                'ts'      => now()->toIso8601String(),
            ];

            // Write feed + spool (include device/ip_hmac only in spool for DB flush)
            $store->appendMessage($msg);
            $clientIp = method_exists($r, 'getClientIp') ? $r->getClientIp() : $r->ip();
            $spoolRow = $msg + [
                'device_id' => Str::substr($device, 0, 64),
                'ip_hmac'   => IpPrivacy::hmac($clientIp),
            ];
            $store->appendSpool($spoolRow);

            // Update last_id cache for peek endpoint (non-critical)
            try { Cache::put('messages:last_id', (int)$msg['id'], 86400); } catch (\Throwable $e) {}

            return response()->json([
                'ok'      => true,
                'message' => $msg,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('message_store_failure', [
                'ex' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_hash' => substr(sha1($e->getTraceAsString()),0,12),
            ]);
            return response()->json([
                'ok' => false,
                'error' => 'storage_failure',
            ], 500);
        }
    }

    private function hasProfanity(string $text): bool {
        $words = array_filter(array_unique(array_map('trim', config('profanity.words', []))));
        if (empty($words)) return false;
        $escaped = array_map(fn($w) => preg_quote($w, '/'), $words);
        $pattern = '/\b(' . implode('|', $escaped) . ')\b/iu';
        return preg_match($pattern, $text) === 1;
    }

    // Lightweight endpoint to let clients check if new messages exist without hitting DB heavily.
    public function peek(Request $r, FeedStore $store) {
        try {
            $cached = Cache::get('messages:last_id');
            if ($cached !== null) {
                return response()->json(['last_id' => (int)$cached]);
            }
        } catch (\Throwable $e) {
            // ignore cache errors and fall through to DB
        }
        // Fallback: read from seq/feed without DB
        $max = $store->lastId();
        try { Cache::put('messages:last_id', (int)$max, 86400); } catch (\Throwable $e) {}
        return response()->json(['last_id' => (int)$max]);
    }
}
