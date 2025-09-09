<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Message> */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'handle'    => $this->faker->optional()->userName(),
            'content'   => $this->faker->sentence(8),
            'device_id' => (string) Str::uuid(),
            'ip_hmac'   => hash('sha256', $this->faker->ipv4()),
            'created_at'=> now(),
            'updated_at'=> now(),
        ];
    }
}

