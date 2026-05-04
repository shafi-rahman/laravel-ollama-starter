<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('model', 50);
            $table->string('endpoint', 10); // chat | stream | sse
            $table->string('prompt_preview', 200);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
