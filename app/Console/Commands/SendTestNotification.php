<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\RealtimeTestNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('notification:test {email : The email address of the user to notify}')]
#[Description('Send a sample notification through the database and broadcast channels.')]
class SendTestNotification extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if ($email === '') {
            $this->components->error('The email argument is required.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->components->error("No user found for [{$email}].");

            return self::FAILURE;
        }

        $user->notify(new RealtimeTestNotification(
            'Test notification',
            'This sample notification was delivered over the database and broadcast channels.',
            route('notifications.index'),
        ));

        $this->components->info("Test notification queued for [{$user->email}].");

        return self::SUCCESS;
    }
}
