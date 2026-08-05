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
        Schema::create('backup_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            /*
             * Null for a scheduled run, which belongs to no one. A deleted
             * administrator must not take backup history with them, so the
             * reference is nulled rather than cascaded.
             */
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();

            /*
             * Holds the single-flight lock at one fixed value while a run is
             * active. The unique index is what makes "only one backup at a time"
             * atomic instead of a read-then-write race between two
             * administrators, or between an administrator and the schedule.
             * Cleared on every terminal transition; duplicate NULLs are allowed
             * by every supported driver, so finished rows never conflict.
             */
            $table->string('lock_key')->nullable()->unique();

            $table->string('status', 32)->index();

            /* The archive this run produced, once it has produced one. */
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('size_in_bytes')->nullable();

            /* A translatable code, never an exception message. */
            $table->string('error_code', 64)->nullable();

            $table->timestamp('started_at')->nullable();

            /* Starts the retention window that pruning measures from. */
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            /* Backs the daily prune of finished rows. */
            $table->index(['status', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_runs');
    }
};
