<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::create('tasks_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->constrained('users');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'pending_approval', 'delivered'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('deadline');
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO tasks_new (id, project_id, title, description, assigned_to, status, priority, deadline, created_at, updated_at)
                       SELECT id, project_id, title, description, assigned_to, status, priority, deadline, created_at, updated_at FROM tasks');

        Schema::drop('tasks');
        DB::statement('ALTER TABLE tasks_new RENAME TO tasks');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys=OFF');

        Schema::create('tasks_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->constrained('users');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'pending_approval'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->date('deadline');
            $table->timestamps();
        });

        DB::statement("INSERT INTO tasks_old (id, project_id, title, description, assigned_to, status, priority, deadline, created_at, updated_at)
                       SELECT id, project_id, title, description, assigned_to,
                              CASE WHEN status = 'delivered' THEN 'completed' ELSE status END,
                              priority, deadline, created_at, updated_at FROM tasks");

        Schema::drop('tasks');
        DB::statement('ALTER TABLE tasks_old RENAME TO tasks');

        DB::statement('PRAGMA foreign_keys=ON');
    }
};
