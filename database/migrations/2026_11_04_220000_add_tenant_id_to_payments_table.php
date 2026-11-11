<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'tenant_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('licensed_deployment_id')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'tenant_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
