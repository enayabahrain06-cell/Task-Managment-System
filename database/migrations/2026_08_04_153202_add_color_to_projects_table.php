<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('status');
        });

        $palette = ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#3B82F6'];
        \App\Models\Project::orderBy('id')->select('id')->get()->each(function ($project, $i) use ($palette) {
            $project->update(['color' => $palette[$i % count($palette)]]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
