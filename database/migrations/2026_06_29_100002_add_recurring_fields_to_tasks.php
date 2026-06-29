<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('delivered_at');
            $table->string('recurring_type')->nullable()->after('is_recurring');   // daily|weekly|monthly
            $table->date('recurring_end_date')->nullable()->after('recurring_type');
            $table->unsignedInteger('recurring_max')->nullable()->after('recurring_end_date');
            $table->unsignedInteger('recurring_count')->default(0)->after('recurring_max');
            $table->foreignId('recurring_parent_id')->nullable()->constrained('tasks')->nullOnDelete()->after('recurring_count');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['recurring_parent_id']);
            $table->dropColumn(['is_recurring','recurring_type','recurring_end_date','recurring_max','recurring_count','recurring_parent_id']);
        });
    }
};
