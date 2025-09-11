<?php
namespace App\Http\Controllers;

use App\Support\FeedStore;
use App\Support\IpPrivacy;
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

        $v = Validator::make($r->all(), [
            'handle'  => 'nullable|string|min:1|max:24',
            'content' => 'required|string|min:1|max:280',
        ]);
        if ($v->fails()) {
            return response()->json(['ok'=>false,'error'=>'validation_failed','details'=>$v->errors()->toArray()], 422);
        }

        $handle  = $r->input('handle');
        $content = (string)$r->input('content');

        // Sanitize: strip HTML, collapse whitespace, trim
        $content = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        if ($content === '') {
            return response()->json(['ok'=>false,'error'=>'validation_failed','details'=>['content'=>['empty_after_sanitization']]], 422);
        }

        if ($this->hasProfanity($content)) {
            return response()->json(['ok'=>false,'error'=>'profanity_blocked'], 422);
        }
        // Build message for feed files
        $msg = [
            'id'      => $store->nextId(),
            'handle'  => $handle ?: null,
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

        // Update last_id cache for peek endpoint
        try { Cache::put('messages:last_id', (int)$msg['id'], 86400); } catch (\Throwable $e) {}

        return response()->json([
            'ok'      => true,
            'message' => $msg,
        ], 201);
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
