<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Services\Addons\AddonRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class AddonPackageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_install_addon_from_zip_upload(): void
    {
        $user = User::factory()->create(['branch_id' => null]);
        Admin::query()->create([
            'user_id' => $user->id,
            'admin_type' => Admin::TYPE_CLIENT,
        ]);

        $zipPath = $this->createAttendanceZip();

        $response = $this->actingAs($user)->post(route('settings.addons.upload'), [
            'package' => new UploadedFile($zipPath, 'attendance.zip', 'application/zip', null, true),
        ]);

        $response->assertOk()->assertJsonPath('addon.slug', 'attendance');

        $this->assertTrue(app(AddonRegistry::class)->isEnabled('attendance'));
        $this->assertFileExists(storage_path('app/addons/manifests/attendance.json'));

        File::delete($zipPath);
    }

    private function createAttendanceZip(): string
    {
        $tempDirectory = storage_path('app/addons/tmp/test-build');
        File::deleteDirectory($tempDirectory);
        File::ensureDirectoryExists($tempDirectory);

        File::copy(base_path('addons/attendance/addon.json'), $tempDirectory.'/addon.json');

        foreach ([
            'app/Addons/Attendance',
            'database/migrations/addons/attendance',
            'resources/views/attendance',
        ] as $relativePath) {
            File::copyDirectory(base_path($relativePath), $tempDirectory.'/'.$relativePath);
        }

        $zipPath = storage_path('app/addons/tmp/attendance-test.zip');
        File::delete($zipPath);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (File::allFiles($tempDirectory) as $file) {
            $relativePath = str_replace('\\', '/', ltrim(str_replace($tempDirectory, '', $file->getPathname()), '/\\'));
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
        File::deleteDirectory($tempDirectory);

        return $zipPath;
    }
}
