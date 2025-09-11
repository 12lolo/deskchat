<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Support\IpPrivacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MessageController extends Controller
{
    public function index(Request $r) {
        $sinceId = max(0, (int)$r->query('since_id', 0));
        $limit   = (int)$r->query('limit', 50);
        if ($limit < 1)  $limit = 1;
        if ($limit > 100) $limit = 100;

        // Use query builder with a narrow column set to avoid Eloquent hydration overhead
        $q = DB::table('messages')->select('id', 'handle', 'content', 'created_at');
        if ($sinceId > 0) $q->where('id', '>', $sinceId);

        $rows = $q->orderBy('id', 'asc')->limit($limit)->get();

        $messages = $rows->map(function ($m) {
            $ts = null;
            if (!empty($m->created_at)) {
                try { $ts = Carbon::parse($m->created_at)->toIso8601String(); } catch (\Throwable $e) { $ts = null; }
            }
            return [
                'id'      => (int)$m->id,
                'handle'  => $m->handle ?? 'Anon',
                'content' => $m->content,
                'ts'      => $ts,
            ];
        });

        $lastId = (int)($rows->last()->id ?? $sinceId);

        return response()->json([
            'messages' => $messages,
            'last_id'  => $lastId,
        ]);
    }

    public function store(Request $r) {
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

        $clientIp = method_exists($r, 'getClientIp') ? $r->getClientIp() : $r->ip();
    $msg = Message::create([
            'handle'    => $handle ?: null,
            'content'   => $content,
            'device_id' => Str::substr($device, 0, 64),
            'ip_hmac'   => IpPrivacy::hmac($clientIp),
        ]);

    // Update cached last_id to support lightweight peek checks without DB hits
    try { Cache::put('messages:last_id', (int)$msg->id, 86400); } catch (\Throwable $e) {}

        return response()->json([
            'ok'      => true,
            'message' => [
                'id'      => (int)$msg->id,
                'handle'  => $msg->handle ?? 'Anon',
                'content' => $msg->content,
                'ts'      => $msg->created_at?->toIso8601String(),
            ],
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
    public function peek(Request $r) {
        try {
            $cached = Cache::get('messages:last_id');
            if ($cached !== null) {
                return response()->json(['last_id' => (int)$cached]);
            }
        } catch (\Throwable $e) {
            // ignore cache errors and fall through to DB
        }
        // Fallback: a single cheap DB query if cache cold
        $max = (int)(Message::max('id') ?? 0);
        // Warm the cache for next time
        try { Cache::put('messages:last_id', $max, 86400); } catch (\Throwable $e) {}
        return response()->json(['last_id' => $max]);
    }
}
