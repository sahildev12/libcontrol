<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicatePhones = DB::table('students')
            ->select('phone')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        foreach ($duplicatePhones as $phone) {
            $ids = DB::table('students')->where('phone', $phone)->orderBy('id')->pluck('id');
            foreach ($ids->slice(1) as $id) {
                DB::table('students')->where('id', $id)->update([
                    'phone' => substr(preg_replace('/\D/', '', (string) $phone).'0000000000', 0, 6).str_pad((string) ($id % 10000), 4, '0', STR_PAD_LEFT),
                ]);
            }
        }

        $duplicateEmails = DB::table('students')
            ->select('email')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        foreach ($duplicateEmails as $email) {
            $ids = DB::table('students')->where('email', $email)->orderBy('id')->pluck('id');
            foreach ($ids->slice(1) as $id) {
                DB::table('students')->where('id', $id)->update([
                    'email' => 'student'.$id.'@libspace.local',
                ]);
            }
        }

        DB::table('students')
            ->where(function ($query) {
                $query->whereNull('email')->orWhere('email', '');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                DB::table('students')->where('id', $row->id)->update([
                    'email' => 'student'.$row->id.'@libspace.local',
                ]);
            });

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

    public function down(): void
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
};
