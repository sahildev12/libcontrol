<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('name');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('father_name')->nullable()->after('date_of_birth');
            $table->string('preparing_for')->nullable()->after('father_name');
            $table->string('photo_path')->nullable()->after('id_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'date_of_birth',
                'father_name',
                'preparing_for',
                'photo_path',
            ]);
        });
    }
};
