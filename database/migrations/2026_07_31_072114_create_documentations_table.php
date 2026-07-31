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
        Schema::create('documentations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('documentation_category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->index();
            $table->unsignedInteger('position')->default(0);
            $table->json('content');
            $table->text('searchable_text');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['documentation_category_id', 'position']);
            $table->index(['status', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentations');
    }
};
