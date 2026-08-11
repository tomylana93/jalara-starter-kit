<?php

namespace Database\Factories\Chat;

use App\Models\Chat\Conversation;
use App\Models\Chat\Participant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'participant_key' => (string) Str::uuid(),
            'last_message_at' => now(),
            /* Set by SendMessage; a conversation with no messages has none. */
            'last_message_id' => null,
        ];
    }

    /**
     * Attach the two sides of the direct message and set the canonical key.
     */
    public function between(User $first, User $second): static
    {
        return $this
            ->state(fn (array $attributes): array => [
                'participant_key' => Conversation::participantKeyFor($first->id, $second->id),
            ])
            ->afterCreating(function (Conversation $conversation) use ($first, $second): void {
                foreach ([$first, $second] as $user) {
                    Participant::factory()->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                    ]);
                }
            });
    }
}
