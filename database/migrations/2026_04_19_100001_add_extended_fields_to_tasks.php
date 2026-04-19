<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->string('task_type', 100)->nullable()->after('reviewer_id');
            $table->json('tags')->nullable()->after('task_type');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['reviewer_id']);
            $table->dropColumn(['created_by', 'reviewer_id', 'task_type', 'tags']);
        });
    }
};
