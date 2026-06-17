<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tracks when a user cleared a direct conversation from their side.
        Schema::create('direct_chat_clears', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('other_user_id');
            $table->timestamp('cleared_at');
            $table->unique(['user_id', 'other_user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('other_user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('message_group_users', function (Blueprint $table) {
            $table->timestamp('cleared_at')->nullable()->after('last_read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_chat_clears');
        Schema::table('message_group_users', function (Blueprint $table) {
            $table->dropColumn('cleared_at');
        });
    }
};
