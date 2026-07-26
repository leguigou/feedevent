<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 30);
            $table->string('filename')->nullable();
            $table->string('status', 30)->default('success');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        DB::table('events')
            ->where('status', 'draft')
            ->where(function ($query) {
                $query->where('llm_meta->connector', 'ics')
                    ->orWhere('llm_meta->connector', 'chrome');
            })
            ->update(['status' => 'published']);
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
