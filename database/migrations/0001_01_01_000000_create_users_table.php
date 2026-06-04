<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------------------------
        // 1. USERS TABLE
        // ---------------------------------------------------------------------
        DB::statement(<<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id BIGSERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NOT NULL,
                remember_token VARCHAR(100),
                email_verified_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                
                CONSTRAINT users_email_unique UNIQUE (email)
            );
        SQL);

        // ---------------------------------------------------------------------
        // 2. PASSWORD RESET TOKENS TABLE
        // ---------------------------------------------------------------------
        DB::statement(<<<SQL
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                email TEXT PRIMARY KEY,
                token TEXT NOT NULL,
                created_at TIMESTAMPTZ
            );
        SQL);

        // ---------------------------------------------------------------------
        // 3. SESSIONS TABLE
        // ---------------------------------------------------------------------
        DB::statement(<<<SQL
            CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id BIGINT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                payload TEXT NOT NULL,
                last_activity INT NOT NULL,

                CONSTRAINT sessions_user_id_fkey 
                    FOREIGN KEY (user_id) 
                    REFERENCES users(id) 
                    ON DELETE CASCADE
            );
        SQL);

        // ---------------------------------------------------------------------
        // 4. INDEXES
        // ---------------------------------------------------------------------
        DB::statement(<<<SQL
            CREATE INDEX IF NOT EXISTS sessions_user_id_idx ON sessions(user_id);
        SQL);

        DB::statement(<<<SQL
            CREATE INDEX IF NOT EXISTS sessions_last_activity_idx ON sessions(last_activity);
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order to cleanly handle dependencies
        DB::statement('DROP TABLE IF EXISTS sessions;');
        DB::statement('DROP TABLE IF EXISTS password_reset_tokens;');
        DB::statement('DROP TABLE IF EXISTS users;');
    }
};