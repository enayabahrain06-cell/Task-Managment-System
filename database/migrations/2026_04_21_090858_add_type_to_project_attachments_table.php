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
        Schema::table('project_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('project_attachments', 'type')) {
                $table->string('type', 10)->default('file')->after('project_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
