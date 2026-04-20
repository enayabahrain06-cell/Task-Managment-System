<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // Change ENUM → VARCHAR so any custom role name is accepted
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'");
        }
        // SQLite stores ENUM as TEXT already — no action needed
        // PostgreSQL: add a check-constraint removal if needed in the future
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','manager','user') NOT NULL DEFAULT 'user'");
        }
    }
};
