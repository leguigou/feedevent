<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('date_start');
            $table->dateTime('date_end')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('address')->nullable();
            $table->string('image_url')->nullable();
            $table->string('source_url')->nullable();
            $table->enum('source_type', ['facebook', 'manual', 'scrape', 'flyer'])->default('manual');
            $table->string('facebook_event_id')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->boolean('is_llm_generated')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('organizer')->nullable();
            $table->json('tags')->nullable();
            $table->json('llm_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('date_start');
            $table->index('status');
            $table->index(['latitude', 'longitude']);
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
