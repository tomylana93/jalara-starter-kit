<?php

namespace App\Support\Chat;

use App\Jobs\Chat\DeliverChatMessageNotification;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Remembers that one user has the dedicated Chat page open, per page instance.
 *
 * A user may have the page open in several tabs at once, so the record is a set
 * of independently expiring contexts rather than a single flag: a tab may only
 * ever remove its own, and the page stays "open" while any other tab's context
 * survives.
 *
 * This is a private, ephemeral suppression context, not presence: nothing about
 * it is broadcast, no other user can observe it, and it never touches a read
 * marker or a receipt. The only reader is
 * {@see DeliverChatMessageNotification}, which skips creating a notification
 * while the page is open, because the page already shows every conversation.
 *
 * Every context expires on its own, so a browser that disappears without
 * cleaning up starts receiving notifications again shortly afterwards rather
 * than staying silent forever.
 */
final class TrackChatPageContext
{
    /**
     * How long one report keeps a single context alive.
     *
     * The client refreshes well inside this window; the margin only has to
     * cover a slow round trip.
     */
    public const int TTL_SECONDS = 90;

    /**
     * How long a writer waits for the user's record before giving up.
     */
    private const int LOCK_SECONDS = 5;

    private const int LOCK_WAIT_SECONDS = 3;

    /**
     * Register the context, or refresh how long it stays alive.
     */
    public function open(User $user, string $contextId): void
    {
        $this->mutate($user, function (array $contexts) use ($contextId): array {
            $contexts[$contextId] = now()->addSeconds(self::TTL_SECONDS)->getTimestamp();

            return $contexts;
        });
    }

    /**
     * Drop one context, leaving every other tab of the same user untouched.
     */
    public function close(User $user, string $contextId): void
    {
        $this->mutate($user, function (array $contexts) use ($contextId): array {
            unset($contexts[$contextId]);

            return $contexts;
        });
    }

    /**
     * Whether at least one of the user's Chat page contexts is still alive.
     */
    public function isOpen(User $user): bool
    {
        return $this->active($user) !== [];
    }

    /**
     * Apply a change to the user's set of contexts.
     *
     * Two tabs can report at the same moment, so the read-modify-write runs
     * under a lock where the cache driver offers one. A driver without locks,
     * or a lock that cannot be acquired in time, falls back to the plain write:
     * the worst case is a lost context, which the client's next refresh
     * restores and the TTL cleans up either way.
     *
     * @param  Closure(array<string, int>): array<string, int>  $callback
     */
    private function mutate(User $user, Closure $callback): void
    {
        $write = function () use ($user, $callback): void {
            $contexts = $callback($this->active($user));

            if ($contexts === []) {
                Cache::forget($this->key($user));

                return;
            }

            /*
             * The record only has to outlive its longest-lived context; each
             * context carries its own expiry inside it.
             */
            Cache::put(
                $this->key($user),
                $contexts,
                max(1, max($contexts) - now()->getTimestamp()),
            );
        };

        if (! Cache::getStore() instanceof LockProvider) {
            $write();

            return;
        }

        try {
            Cache::lock($this->key($user).'.lock', self::LOCK_SECONDS)
                ->block(self::LOCK_WAIT_SECONDS, $write);
        } catch (LockTimeoutException) {
            $write();
        }
    }

    /**
     * The user's contexts that have not expired yet.
     *
     * @return array<string, int>
     */
    private function active(User $user): array
    {
        $stored = Cache::get($this->key($user));

        if (! is_array($stored)) {
            return [];
        }

        $now = now()->getTimestamp();

        /** @var array<string, int> $active */
        $active = array_filter(
            $stored,
            fn (mixed $expiresAt): bool => is_int($expiresAt) && $expiresAt > $now,
        );

        return $active;
    }

    private function key(User $user): string
    {
        return 'chat.page.'.$user->id;
    }
}
