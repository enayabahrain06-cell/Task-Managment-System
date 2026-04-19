<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate data to new status vocabulary (SQLite stores enum as TEXT, so just UPDATE)
        DB::table('tasks')->where('status', 'pending_approval')->update(['status' => 'submitted']);
        DB::table('tasks')->where('status', 'completed')->update(['status' => 'approved']);
        DB::table('tasks')->where('status', 'pending')->whereNotNull('assigned_to')->update(['status' => 'assigned']);
        DB::table('tasks')->where('status', 'pending')->whereNull('assigned_to')->update(['status' => 'draft']);

        // in_progress and delivered stay the same
    }

    public function down(): void
    {
        DB::table('tasks')->where('status', 'submitted')->update(['status' => 'pending_approval']);
        DB::table('tasks')->where('status', 'approved')->update(['status' => 'completed']);
        DB::table('tasks')->whereIn('status', ['assigned', 'draft', 'viewed'])->update(['status' => 'pending']);
        DB::table('tasks')->where('status', 'revision_requested')->update(['status' => 'in_progress']);
        DB::table('tasks')->where('status', 'archived')->update(['status' => 'pending']);
    }
};
