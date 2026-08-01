<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            /*
             * The canonical identity of a direct message: both participant ids
             * sorted and joined. The unique index is what prevents a second
             * conversation ever being opened for the same pair, whichever side
             * sends first.
             */
            $table->string('participant_key')->unique();

            /* Drives the inbox ordering. */
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('chat_participants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            /* Read receipt and unread marker for this side of the conversation. */
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->cascadeOnDelete();

            /* Messages are immutable: nothing in the application updates this column. */
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_mime_type', 100)->nullable();
            $table->timestamps();

            /* Supports the newest-first window and the keyset scroll into history. */
            $table->index(['conversation_id', 'created_at', 'id']);
        });

        Schema::create('chat_message_reactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_reactions');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_participants');
        Schema::dropIfExists('chat_conversations');
    }
};
