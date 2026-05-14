<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->string('nas_path', 1024)->nullable()->after('file_path');
        });

        Schema::table('project_attachments', function (Blueprint $table) {
            $table->string('nas_path', 1024)->nullable()->after('path');
        });

        Schema::table('task_comments', function (Blueprint $table) {
            $table->string('nas_path', 1024)->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('task_submissions', function (Blueprint $table) {
            $table->dropColumn('nas_path');
        });
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->dropColumn('nas_path');
        });
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropColumn('nas_path');
        });
    }
};
