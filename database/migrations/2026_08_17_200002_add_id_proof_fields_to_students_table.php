<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('id_proof_type')->nullable()->after('email');
            $table->string('id_proof_path')->nullable()->after('id_proof_type');
            $table->text('address')->nullable()->after('id_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['id_proof_type', 'id_proof_path', 'address']);
        });
    }
};
