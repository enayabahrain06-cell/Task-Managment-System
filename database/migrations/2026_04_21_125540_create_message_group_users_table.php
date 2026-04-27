<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('message_group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('message_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('message_group_users'); }
};
