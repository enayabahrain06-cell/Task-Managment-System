<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor')->nullable();
            $table->string('category')->default('other');
            $table->string('type')->default('per_seat');
            $table->string('billing_cycle')->default('annual');
            $table->decimal('cost', 10, 3)->default(0);
            $table->string('currency', 10)->default('BHD');
            $table->unsignedInteger('max_seats')->nullable();
            $table->string('website')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->json('notify_days')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
