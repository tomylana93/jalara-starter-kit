<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Give a conversation a direct pointer to its newest message.
 *
 * `latestMessage` was a `hasOne(...)->latestOfMany()` relation. Laravel builds
 * those by joining a subquery that aggregates the related table, and it always
 * adds the primary key as a tiebreaker aggregate - `MAX(id)` - even when another
 * sort column is given. PostgreSQL has no `MAX` for the `uuid` type, so on the
 * production engine every query touching that relation failed outright with
 * "function max(uuid) does not exist", taking the whole chat inbox to a 500.
 * The framework documents this as a hard limitation of one-of-many relations
 * with UUID keys rather than something a different sort column can avoid.
 *
 * A maintained pointer replaces the aggregate. `last_message_at` was already
 * written on the same line of the same transaction, so this column costs the
 * send path nothing new and turns the relation into a plain `belongsTo`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table): void {
            /*
             * Nulled rather than cascaded: a deleted message must not take the
             * conversation with it. Pruning the newest message leaves the
             * pointer null until the next send, which reads as "no preview yet"
             * exactly like a conversation nobody has written in.
             */
            $table->foreignUuid('last_message_id')
                ->nullable()
                ->after('last_message_at')
                ->constrained('chat_messages')
                ->nullOnDelete();
        });

        /*
         * Backfill by the same ordering the inbox uses: newest instant first,
         * id as the tiebreak so two messages sharing a timestamp resolve to one
         * answer. A correlated subquery with ORDER BY and LIMIT is the form both
         * SQLite and PostgreSQL accept.
         */
        DB::statement(<<<'SQL'
            update chat_conversations
            set last_message_id = (
                select chat_messages.id
                from chat_messages
                where chat_messages.conversation_id = chat_conversations.id
                order by chat_messages.created_at desc, chat_messages.id desc
                limit 1
            )
        SQL);
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('last_message_id');
        });
    }
};
