<?php

namespace Database\Factories\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->sentence(),
            'image_path' => null,
            'image_mime_type' => null,
        ];
    }

    public function withImage(): static
    {
        return $this->state(fn (): array => [
            'image_path' => 'chat/'.fake()->uuid().'.png',
            'image_mime_type' => 'image/png',
        ]);
    }
}
