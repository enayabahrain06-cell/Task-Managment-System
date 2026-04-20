<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();     // slug: admin, manager, designer …
            $table->string('label', 80);              // display: Admin, Designer …
            $table->string('color', 7)->default('#6366F1'); // hex badge color
            $table->string('description', 200)->nullable();
            $table->boolean('is_system')->default(false); // system roles cannot be deleted
            $table->timestamps();
        });

        // Seed the three built-in roles
        DB::table('roles')->insert([
            ['name' => 'admin',   'label' => 'Admin',   'color' => '#EF4444', 'description' => 'Full system access. Can manage users, roles, and all settings.', 'is_system' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manager', 'label' => 'Manager', 'color' => '#F59E0B', 'description' => 'Can create projects, assign tasks, and review deliverables.',       'is_system' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'user',    'label' => 'User',    'color' => '#10B981', 'description' => 'Standard member. Works on assigned tasks and submits deliverables.', 'is_system' => true,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
