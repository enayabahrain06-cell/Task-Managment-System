<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make the column nullable before setting NULL values (SQLite enforces NOT NULL otherwise)
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('mfa_required')->nullable()->default(null)->change();
        });

        // Rows with 0 (= "never explicitly set") become null to mean "follow global force_mfa policy".
        // New semantics: null = follow global, 1 = always required, 0 = admin-exempted.
        DB::statement('UPDATE users SET mfa_required = NULL WHERE mfa_required = 0');
    }

    public function down(): void
    {
        DB::statement('UPDATE users SET mfa_required = 0 WHERE mfa_required IS NULL');
    }
};
