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
        Schema::create('image_uploads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->string('target', 32);

            /* The branding asset for a branding upload; null for the others. */
            $table->string('target_key')->nullable();

            /*
             * Holds the active-target lock. The unique index is what makes
             * "one upload at a time per avatar or branding asset" atomic rather
             * than a read-then-write race. It is cleared the moment the upload
             * reaches a terminal state, and duplicate NULLs are allowed by every
             * supported driver, so non-exclusive targets such as chat images
             * simply never take the lock.
             */
            $table->string('lock_key')->nullable()->unique();

            $table->string('status', 32)->index();

            /* Private staging copy of the bytes the user sent. Never public. */
            $table->string('source_path');
            $table->string('source_mime_type', 100);

            /* Populated only once processing has produced a stored image. */
            $table->string('result_path')->nullable();
            $table->string('result_mime_type', 100)->nullable();

            /*
             * Target-specific data the job needs to publish the result, such as
             * the conversation and body of a pending chat message. Encrypted at
             * rest and never serialized to the client.
             */
            $table->text('payload')->nullable();

            /* A translatable code, never an exception message. */
            $table->string('error_code', 64)->nullable();

            /* Starts the retention window that pruning measures from. */
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            /* Backs the owner's "restore my active uploads" query after a reload. */
            $table->index(['user_id', 'status']);

            /* Backs the daily prune of terminal records. */
            $table->index(['status', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_uploads');
    }
};
