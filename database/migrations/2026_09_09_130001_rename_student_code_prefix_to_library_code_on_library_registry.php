<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('library_registry')) {
            return;
        }

        if (Schema::hasColumn('library_registry', 'student_code_prefix') && ! Schema::hasColumn('library_registry', 'library_code')) {
            Schema::table('library_registry', function (Blueprint $table) {
                $table->renameColumn('student_code_prefix', 'library_code');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('library_registry')) {
            return;
        }

        if (Schema::hasColumn('library_registry', 'library_code') && ! Schema::hasColumn('library_registry', 'student_code_prefix')) {
            Schema::table('library_registry', function (Blueprint $table) {
                $table->renameColumn('library_code', 'student_code_prefix');
            });
        }
    }
};
