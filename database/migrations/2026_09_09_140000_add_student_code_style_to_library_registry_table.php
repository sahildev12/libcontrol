<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_registry', function (Blueprint $table) {
            $table->string('student_code_prefix', 20)->nullable()->after('client_name');
            $table->unsignedTinyInteger('student_code_padding')->default(3)->after('student_code_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('library_registry', function (Blueprint $table) {
            $table->dropColumn(['student_code_prefix', 'student_code_padding']);
        });
    }
};
