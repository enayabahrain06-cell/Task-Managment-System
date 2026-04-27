<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('social_assigned_to')->nullable()->after('assigned_to');
            $table->timestamp('social_posted_at')->nullable()->after('social_assigned_to');
            $table->foreign('social_assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['social_assigned_to']);
            $table->dropColumn(['social_assigned_to', 'social_posted_at']);
        });
    }
};
