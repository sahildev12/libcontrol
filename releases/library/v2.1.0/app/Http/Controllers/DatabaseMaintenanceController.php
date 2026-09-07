<?php

namespace App\Http\Controllers;

use App\Services\DatabaseMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseMaintenanceController extends Controller
{
    public function status(DatabaseMaintenanceService $maintenance): JsonResponse
    {
        return response()->json([
            'status' => $maintenance->status(),
            'backups' => $maintenance->listBackups(),
        ]);
    }

    public function backup(DatabaseMaintenanceService $maintenance): JsonResponse
    {
        $backup = $maintenance->createBackup();

        return response()->json([
            'message' => 'Database backup created successfully.',
            'backup' => $backup,
            'backups' => $maintenance->listBackups(),
        ]);
    }

    public function migrate(DatabaseMaintenanceService $maintenance): JsonResponse
    {
        $result = $maintenance->runMigrations();

        return response()->json([
            'message' => 'Database migrations completed.',
            'output' => $result['output'],
            'status' => $maintenance->status(),
        ]);
    }

    public function restore(Request $request, DatabaseMaintenanceService $maintenance): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'confirmation' => ['required', 'string', 'in:RESTORE'],
        ]);

        $maintenance->restoreBackup($validated['filename']);

        return response()->json([
            'message' => 'Database restored from backup.',
            'status' => $maintenance->status(),
        ]);
    }

    public function destroy(Request $request, DatabaseMaintenanceService $maintenance): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $maintenance->deleteBackup($validated['filename']);

        return response()->json([
            'message' => 'Backup deleted.',
            'backups' => $maintenance->listBackups(),
        ]);
    }

    public function download(Request $request, DatabaseMaintenanceService $maintenance): BinaryFileResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $path = $maintenance->resolveBackupPath($validated['filename']);

        return response()->download($path);
    }
}
