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
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('design_sent_at')->nullable()->after('first_viewed_at');
            $table->timestamp('customer_approved_at')->nullable()->after('design_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['design_sent_at', 'customer_approved_at']);
        });
    }
};
