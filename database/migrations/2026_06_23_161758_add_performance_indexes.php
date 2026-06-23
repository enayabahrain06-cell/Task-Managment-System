<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tasks — correlated subqueries filter on customer_id, project_id, status, deleted_at
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('customer_id',  'tasks_customer_id_idx');
            $table->index('project_id',   'tasks_project_id_idx');
            $table->index('assigned_to',  'tasks_assigned_to_idx');
            $table->index('status',       'tasks_status_idx');
            $table->index('deadline',     'tasks_deadline_idx');
            $table->index('deleted_at',   'tasks_deleted_at_idx');
            $table->index('created_at',   'tasks_created_at_idx');
            $table->index(['customer_id', 'status'], 'tasks_customer_status_idx');
            $table->index(['project_id',  'status'], 'tasks_project_status_idx');
        });

        // projects — filtered by customer_id and is_quick on nearly every query
        Schema::table('projects', function (Blueprint $table) {
            $table->index('customer_id',               'projects_customer_id_idx');
            $table->index('is_quick',                  'projects_is_quick_idx');
            $table->index(['customer_id', 'is_quick'], 'projects_customer_quick_idx');
        });

        // task_logs — timeline queries, sorted by created_at
        Schema::table('task_logs', function (Blueprint $table) {
            $table->index('task_id',              'task_logs_task_id_idx');
            $table->index('user_id',              'task_logs_user_id_idx');
            $table->index('created_at',           'task_logs_created_at_idx');
            $table->index(['task_id', 'created_at'], 'task_logs_task_created_idx');
        });

        // notifications — pruned and fetched per user on every page load
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('created_at', 'notifications_created_at_idx');
            $table->index('read_at',    'notifications_read_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_customer_id_idx');
            $table->dropIndex('tasks_project_id_idx');
            $table->dropIndex('tasks_assigned_to_idx');
            $table->dropIndex('tasks_status_idx');
            $table->dropIndex('tasks_deadline_idx');
            $table->dropIndex('tasks_deleted_at_idx');
            $table->dropIndex('tasks_created_at_idx');
            $table->dropIndex('tasks_customer_status_idx');
            $table->dropIndex('tasks_project_status_idx');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_customer_id_idx');
            $table->dropIndex('projects_is_quick_idx');
            $table->dropIndex('projects_customer_quick_idx');
        });

        Schema::table('task_logs', function (Blueprint $table) {
            $table->dropIndex('task_logs_task_id_idx');
            $table->dropIndex('task_logs_user_id_idx');
            $table->dropIndex('task_logs_created_at_idx');
            $table->dropIndex('task_logs_task_created_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_created_at_idx');
            $table->dropIndex('notifications_read_at_idx');
        });
    }
};
