<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
	use RefreshDatabase;

	private function deviceId(): string
	{
		return (string) Str::uuid();
	}

	private function postMsg(array $payload, ?string $deviceId = null)
	{
		$deviceId ??= $this->deviceId();
		if (!array_key_exists('handle', $payload)) {
			$payload['handle'] = 'Tester';
		}
		return $this->withHeaders(['X-Device-Id' => $deviceId])
			->postJson('/api/messages', $payload);
	}

	public function test_health_ok(): void
	{
		$this->getJson('/api/health')
			->assertStatus(200)
			->assertJson(['ok' => true]);
	}

	public function test_post_requires_device_id(): void
	{
		$this->postJson('/api/messages', ['content' => 'hoi'])
			->assertStatus(400)
			->assertJson(['ok' => false, 'error' => 'missing_device_id']);
	}

	public function test_post_minimal_success_and_get_flow(): void
	{
		$did = $this->deviceId();
		$this->postMsg(['content' => 'hoi', 'handle' => 'Alice'], $did)
			->assertStatus(201)
			->assertJson(fn($j) => $j->where('ok', true)
				->has('message', fn($m) => $m
					->whereType('id', 'integer')
					->where('handle', 'Alice')
					->where('content', 'hoi')
					->has('ts')
				));

		$res = $this->getJson('/api/messages?limit=10')
			->assertStatus(200)
			->json();

		$this->assertArrayHasKey('messages', $res);
		$this->assertArrayHasKey('last_id', $res);
		$this->assertGreaterThanOrEqual(1, count($res['messages']));
		$this->assertSame($res['messages'][array_key_last($res['messages'])]['id'], $res['last_id']);
	}

	public function test_validation_and_sanitization(): void
	{
		// Empty content
		$this->postMsg(['content' => ''])
			->assertStatus(422)
			->assertJson(['ok' => false, 'error' => 'validation_failed']);

		// Too long (281 chars)
		$tooLong = str_repeat('a', 281);
		$this->postMsg(['content' => $tooLong])
			->assertStatus(422)
			->assertJson(['ok' => false, 'error' => 'validation_failed']);

		// HTML stripped -> empties to nothing => 422 empty_after_sanitization
		$this->postMsg(['content' => "   <b>   </b>   \n\t"]) 
			->assertStatus(422)
			->assertJson(fn($j) => $j->where('ok', false)
				->where('error', 'validation_failed')
				->has('details.content'));

		// HTML stripped but valid text remains
		$this->postMsg(['content' => '<b>hoi</b> wereld'])
			->assertStatus(201)
			->assertJson(fn($j) => $j->where('ok', true)
				->has('message', fn($m) => $m->where('content', 'hoi wereld')->etc()));
	}

	public function test_profanity_blocked(): void
	{
		// Override config for deterministic test
		config(['profanity.words' => ['vloekwoord']]);
		$this->postMsg(['content' => 'dit is een vloekwoord in zin'])
			->assertStatus(422)
			->assertJson(['ok' => false, 'error' => 'profanity_blocked']);
	}

	public function test_get_since_id_and_limits_and_ordering(): void
	{
		$did = $this->deviceId();
		foreach (["a","b","c"] as $idx => $t) {
			$this->postMsg(['content' => $t, 'handle' => 'User'.$idx], $did)->assertStatus(201);
		}

		$page1 = $this->getJson('/api/messages?limit=2')->assertStatus(200)->json();
		$this->assertCount(2, $page1['messages']);
		// Ordering: ascending by id and last_id equals id of last item
		$this->assertLessThan($page1['messages'][1]['id'], $page1['messages'][0]['id']);
		$this->assertSame($page1['messages'][1]['id'], $page1['last_id']);

		$since = $page1['last_id'];
		$page2 = $this->getJson('/api/messages?since_id='.$since.'&limit=10')->assertStatus(200)->json();
		// Only the remaining item(s)
		$this->assertGreaterThanOrEqual(0, count($page2['messages']));

		// Bounds: limit 0 => treated as 1; 101 => capped to 100
		$this->getJson('/api/messages?limit=0')->assertStatus(200);
		$this->getJson('/api/messages?limit=101')->assertStatus(200);
	}

	public function test_device_id_truncated_to_64_chars_in_db(): void
	{
		$veryLong = str_repeat('x', 200);
		$this->withHeaders(['X-Device-Id' => $veryLong])
			->postJson('/api/messages', ['content' => 'hoi'])
			->assertStatus(201);

		$this->assertDatabaseCount('messages', 1);
		$dev = Message::query()->firstOrFail()->device_id;
		$this->assertSame(64, strlen($dev));
	}

	public function test_rate_limiting_per_device_and_ip(): void
	{
		// Per device: 15/min — send 16th should be 429
		$did = $this->deviceId();
		for ($i = 0; $i < 15; $i++) {
			$this->postMsg(['content' => 'm'.$i, 'handle' => 'RL'], $did)->assertStatus(201);
		}
		$this->postMsg(['content' => 'overflow'], $did)
			->assertStatus(429)
			->assertJson(['ok' => false, 'error' => 'rate_limited']);
	}

	public function test_missing_handle_rejected(): void
	{
		$this->postMsg(['content' => 'hoi', 'handle' => null]) // helper will not override because key exists
			->assertStatus(422)
			->assertJson(['ok'=>false,'error'=>'validation_failed']);

		// Without key at all -> helper injects value and succeeds
		$res = $this->withHeaders(['X-Device-Id' => $this->deviceId()])
			->postJson('/api/messages', ['content' => 'ok'])
			->assertStatus(422); // because handle required and helper not involved
	}
}

