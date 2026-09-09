<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->update([
            'app_pin_hash' => null,
            'app_pin_set_at' => null,
        ]);
    }

    public function down(): void
    {
        // PINs are hashed and cannot be restored.
    }
};
