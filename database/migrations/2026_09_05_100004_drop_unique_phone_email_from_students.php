<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexNames = collect(Schema::getIndexes('students'))->pluck('name')->all();

        Schema::table('students', function (Blueprint $table) use ($indexNames) {
            if (in_array('students_phone_unique', $indexNames, true)) {
                $table->dropUnique(['phone']);
            }
            if (in_array('students_email_unique', $indexNames, true)) {
                $table->dropUnique(['email']);
            }
        });
    }

    public function down(): void
    {
        $indexNames = collect(Schema::getIndexes('students'))->pluck('name')->all();

        Schema::table('students', function (Blueprint $table) use ($indexNames) {
            if (! in_array('students_phone_unique', $indexNames, true)) {
                $table->unique('phone');
            }
            if (! in_array('students_email_unique', $indexNames, true)) {
                $table->unique('email');
            }
        });
    }
};
