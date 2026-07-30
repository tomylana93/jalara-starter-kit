<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Queue('notifications')]
#[Tries(3)]
#[Backoff(60, 300)]
#[Timeout(30)]
#[DeleteWhenMissingModels]
class SendEmailVerification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        if ($this->user->hasVerifiedEmail()) {
            return;
        }

        $this->user->notify(new VerifyEmail);
    }
}
