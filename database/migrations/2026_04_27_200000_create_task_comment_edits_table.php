<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('task_comment_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_comment_id')->constrained()->cascadeOnDelete();
            $table->text('old_body');
            $table->string('old_file_path')->nullable();
            $table->string('old_original_filename')->nullable();
            $table->foreignId('edited_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('task_comment_edits'); }
};
