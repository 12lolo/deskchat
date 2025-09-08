<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Support\IpPrivacy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function index(Request $r) {
        $sinceId = max(0, (int)$r->query('since_id', 0));
        $limit   = (int)$r->query('limit', 50);
        if ($limit < 1)  $limit = 1;
        if ($limit > 100) $limit = 100;

        $q = Message::query();
        if ($sinceId > 0) $q->where('id', '>', $sinceId);

        $rows = $q->orderBy('id', 'asc')->limit($limit)->get();

        $messages = $rows->map(fn($m) => [
            'id'      => (int)$m->id,
            'handle'  => $m->handle ?? 'Anon',
            'content' => $m->content,
            'ts'      => $m->created_at?->toIso8601String(),
        ]);

        $lastId = $rows->last()->id ?? $sinceId;

        return response()->json([
            'messages' => $messages,
            'last_id'  => (int)$lastId,
        ]);
    }

    public function store(Request $r) {
        $device = trim((string)$r->header('X-Device-Id', ''));
        if ($device === '') {
            return response()->json(['ok'=>false,'error'=>'missing_device_id'], 400);
        }

        $data = $r->validate([
            'handle'  => 'nullable|string|min:1|max:24',
            'content' => 'required|string|min:1|max:280',
        ]);

        $content = trim(strip_tags($data['content']));
        $content = preg_replace('/\s+/u', ' ', $content);

        if ($this->hasProfanity($content)) {
            return response()->json(['ok'=>false,'error'=>'profanity_blocked'], 422);
        }

        $msg = Message::create([
            'handle'    => $data['handle'] ?? null,
            'content'   => $content,
            'device_id' => Str::substr($device, 0, 64),
            'ip_hmac'   => IpPrivacy::hmac($r->ip()),
        ]);

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
        // word boundaries, case-insensitive, unicode
        $pattern = '/\b(' . implode('|', $escaped) . ')\b/iu';
        return preg_match($pattern, $text) === 1;
    }
}
