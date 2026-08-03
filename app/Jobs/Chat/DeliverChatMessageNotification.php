<?php

namespace App\Jobs\Chat;

use App\Actions\Chat\NotifyChatMessageRecipient;
use App\Models\Chat\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Carries the recipient's chat notification onto the queue.
 *
 * Transport only: which recipient hears about the message, and whether they
 * hear about it at all, is decided by `NotifyChatMessageRecipient`, so that
 * decision stays testable and callable without a worker.
 */
class DeliverChatMessageNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function handle(NotifyChatMessageRecipient $notifyChatMessageRecipient): void
    {
        $notifyChatMessageRecipient->handle($this->message);
    }
}
