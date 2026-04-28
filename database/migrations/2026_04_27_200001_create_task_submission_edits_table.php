<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('task_submission_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_submission_id')->constrained()->cascadeOnDelete();
            $table->text('old_note')->nullable();
            $table->foreignId('edited_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('task_submission_edits'); }
};
