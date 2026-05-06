<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_timer_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phase')->default('work'); // work | revision | review | social
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable(); // null = currently running
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('pause_reason')->nullable(); // manual | task_switch | end_of_day | submitted | approved | system
            $table->timestamps();

            $table->index(['task_id', 'user_id']);
            $table->index(['user_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_timer_segments');
    }
};
