<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement("CREATE TABLE users_new (
            id         INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name       VARCHAR(255) NOT NULL,
            email      VARCHAR(255) NOT NULL UNIQUE,
            email_verified_at DATETIME NULL,
            password   VARCHAR(255) NOT NULL,
            role       VARCHAR(255) NOT NULL DEFAULT 'user',
            remember_token VARCHAR(100) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            avatar     VARCHAR(255) NULL,
            phone      VARCHAR(30) NULL,
            job_title  VARCHAR(80) NULL,
            status     VARCHAR(255) NOT NULL DEFAULT 'active'
                           CHECK(status IN ('active','inactive','archived')),
            archived_at DATETIME NULL,
            archived_by INTEGER NULL
        )");

        DB::statement("INSERT INTO users_new
            (id, name, email, email_verified_at, password, role, remember_token,
             created_at, updated_at, avatar, phone, job_title, status)
            SELECT id, name, email, email_verified_at, password, role, remember_token,
                   created_at, updated_at, avatar, phone, job_title, status
            FROM users");

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_new RENAME TO users');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement("CREATE TABLE users_new (
            id         INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name       VARCHAR(255) NOT NULL,
            email      VARCHAR(255) NOT NULL UNIQUE,
            email_verified_at DATETIME NULL,
            password   VARCHAR(255) NOT NULL,
            role       VARCHAR(255) NOT NULL DEFAULT 'user',
            remember_token VARCHAR(100) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            avatar     VARCHAR(255) NULL,
            phone      VARCHAR(30) NULL,
            job_title  VARCHAR(80) NULL,
            status     VARCHAR(255) NOT NULL DEFAULT 'active'
                           CHECK(status IN ('active','inactive'))
        )");

        DB::statement("INSERT INTO users_new
            (id, name, email, email_verified_at, password, role, remember_token,
             created_at, updated_at, avatar, phone, job_title, status)
            SELECT id, name, email, email_verified_at, password, role, remember_token,
                   created_at, updated_at, avatar, phone, job_title,
                   CASE WHEN status = 'archived' THEN 'inactive' ELSE status END
            FROM users");

        DB::statement('DROP TABLE users');
        DB::statement('ALTER TABLE users_new RENAME TO users');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
