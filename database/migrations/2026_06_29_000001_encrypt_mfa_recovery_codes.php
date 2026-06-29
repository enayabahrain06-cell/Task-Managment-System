<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('mfa_recovery_codes')
            ->get(['id', 'mfa_recovery_codes'])
            ->each(function ($user) {
                // Skip if already encrypted (Laravel payloads are base64-encoded JSON starting with eyJ)
                if (str_starts_with($user->mfa_recovery_codes, 'eyJ')) {
                    return;
                }
                $decoded = json_decode($user->mfa_recovery_codes, true);
                if (is_array($decoded)) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['mfa_recovery_codes' => Crypt::encryptString(json_encode($decoded))]);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNotNull('mfa_recovery_codes')
            ->get(['id', 'mfa_recovery_codes'])
            ->each(function ($user) {
                try {
                    $plain = Crypt::decryptString($user->mfa_recovery_codes);
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['mfa_recovery_codes' => $plain]);
                } catch (\Throwable) {
                    // Already plaintext
                }
            });
    }
};
