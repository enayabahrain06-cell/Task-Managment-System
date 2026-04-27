<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('receiver_id')
                  ->constrained('message_groups')->onDelete('cascade');
            $table->unsignedBigInteger('receiver_id')->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
            $table->unsignedBigInteger('receiver_id')->nullable(false)->change();
        });
    }
};
