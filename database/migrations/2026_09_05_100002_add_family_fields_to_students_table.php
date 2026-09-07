<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('family_group_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->boolean('is_family_primary')->default(false)->after('family_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_group_id');
            $table->dropColumn('is_family_primary');
        });
    }
};
