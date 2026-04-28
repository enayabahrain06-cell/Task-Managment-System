<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_quick')->default(false)->after('status');
        });

        // Mark the existing auto-created quick-tasks container
        \App\Models\Project::where('name', 'Quick Tasks')->update(['is_quick' => true]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('is_quick');
        });
    }
};
