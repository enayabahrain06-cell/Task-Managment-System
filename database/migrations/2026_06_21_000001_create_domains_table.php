<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('registrar')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('billing_to')->nullable();
            $table->decimal('cost', 10, 3)->default(0);
            $table->string('currency', 10)->default('BHD');
            $table->string('billing_cycle')->default('annual');
            $table->boolean('auto_renew')->default(false);
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('notify_days')->nullable();
            $table->json('nameservers')->nullable();
            $table->string('hosting_provider')->nullable();
            $table->string('login_url')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
