<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<SQL
            CREATE TABLE exams (
                id BIGSERIAL PRIMARY KEY,
                title TEXT NOT NULL,
                description TEXT,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            );
        SQL);

        DB::statement(<<<SQL
            CREATE TABLE submissions (
                id BIGSERIAL PRIMARY KEY,

                user_id BIGINT NOT NULL,
                exam_id BIGINT NOT NULL,

                code TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'draft',

                review_token_hash TEXT,
                review_token_expires_at TIMESTAMPTZ,

                submitted_at TIMESTAMPTZ,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

                CONSTRAINT submissions_status_check
                    CHECK (status IN ('draft', 'submitted')),

                CONSTRAINT submissions_exam_id_fkey
                    FOREIGN KEY (exam_id)
                    REFERENCES exams(id)
                    ON DELETE CASCADE
            );
        SQL);

        DB::statement(<<<SQL
            CREATE INDEX submissions_user_id_index
            ON submissions(user_id);
        SQL);

        DB::statement(<<<SQL
            CREATE INDEX submissions_exam_id_index
            ON submissions(exam_id);
        SQL);

        DB::statement(<<<SQL
            CREATE UNIQUE INDEX submissions_user_exam_unique
            ON submissions(user_id, exam_id);
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS submissions;');
        DB::statement('DROP TABLE IF EXISTS exams;');
    }
};
