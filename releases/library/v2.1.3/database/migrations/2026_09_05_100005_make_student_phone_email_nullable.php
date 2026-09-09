<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF');
            DB::statement('CREATE TABLE students__new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                branch_id INTEGER NOT NULL,
                family_group_id INTEGER NULL,
                is_family_primary TINYINT(1) NOT NULL DEFAULT 0,
                student_code VARCHAR NOT NULL,
                name VARCHAR NOT NULL,
                gender VARCHAR,
                date_of_birth DATE,
                father_name VARCHAR,
                preparing_for VARCHAR,
                phone VARCHAR,
                email VARCHAR,
                id_proof_type VARCHAR,
                id_proof_path VARCHAR,
                photo_path VARCHAR,
                address TEXT,
                status VARCHAR NOT NULL DEFAULT \'active\',
                student_type VARCHAR NOT NULL DEFAULT \'regular\',
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE CASCADE,
                FOREIGN KEY(family_group_id) REFERENCES family_groups(id) ON DELETE SET NULL
            )');
            DB::statement('INSERT INTO students__new SELECT
                id, branch_id, family_group_id, is_family_primary, student_code, name, gender, date_of_birth,
                father_name, preparing_for, phone, email, id_proof_type, id_proof_path, photo_path, address,
                status, student_type, created_at, updated_at
                FROM students');
            DB::statement('DROP TABLE students');
            DB::statement('ALTER TABLE students__new RENAME TO students');
            DB::statement('CREATE UNIQUE INDEX students_student_code_unique ON students (student_code)');
            DB::statement('PRAGMA foreign_keys=ON');

            return;
        }

        DB::statement('ALTER TABLE students MODIFY phone VARCHAR(20) NULL');
        DB::statement('ALTER TABLE students MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE students MODIFY phone VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE students MODIFY email VARCHAR(255) NOT NULL');
    }
};
