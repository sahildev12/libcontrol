<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\DatabaseMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class DatabaseMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_admin_can_create_sqlite_backup(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $response = $this->actingAs($user)->postJson(route('settings.database.backup'));

        $response->assertOk()->assertJsonPath('backup.filename', fn ($value) => str_ends_with((string) $value, '.sqlite'));

        $filename = $response->json('backup.filename');
        $this->assertTrue(File::exists(app(DatabaseMaintenanceService::class)->resolveBackupPath($filename)));
    }

    public function test_developer_admin_can_run_migrations_endpoint(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_DEVELOPER,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.database.migrate'))
            ->assertOk()
            ->assertJsonPath('message', 'Database migrations completed.');
    }

    public function test_platform_admin_cannot_access_database_tools(): void
    {
        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $this->actingAs($user)
            ->postJson(route('settings.database.backup'))
            ->assertForbidden();
    }

    public function test_branch_user_cannot_access_database_tools(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('settings.database.backup'))
            ->assertForbidden();
    }
}
