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
        /*
         * Access metadata only: the audit trail records that a conversation was
         * opened, never a copy of what was read. Rows are permanent and nothing
         * in the application deletes them.
         */
        Schema::create('chat_audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->foreignUuid('viewer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'viewed_at']);
            $table->index(['viewer_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_audit_logs');
    }
};
