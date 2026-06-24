<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('mfa_secret')
            ->get(['id', 'mfa_secret'])
            ->each(function ($user) {
                // Skip if already encrypted (Laravel payloads are base64-encoded JSON starting with eyJ)
                if (! str_starts_with($user->mfa_secret, 'eyJ')) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['mfa_secret' => Crypt::encryptString($user->mfa_secret)]);
                }
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNotNull('mfa_secret')
            ->get(['id', 'mfa_secret'])
            ->each(function ($user) {
                try {
                    $plain = Crypt::decryptString($user->mfa_secret);
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['mfa_secret' => $plain]);
                } catch (\Throwable) {
                    // Already plaintext, nothing to do
                }
            });
    }
};
