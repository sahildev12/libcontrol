<?php

namespace App\Http\Controllers;

use App\Services\StudentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    public function template(StudentImportService $importService): StreamedResponse
    {
        $spreadsheet = $importService->templateSpreadsheet();

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'student-import-sample.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function store(Request $request, StudentImportService $importService): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ], [
            'file.required' => 'Choose an Excel or CSV file to import.',
            'file.mimes' => 'Upload an .xlsx, .xls or .csv file.',
            'file.max' => 'The file must be 5 MB or smaller.',
        ]);

        $branch = $this->resolveWritableBranch($request, $validated['branch_id'] ?? null);

        $result = $importService->import($branch, $request->file('file'));

        if ($result['errors'] !== []) {
            return response()->json([
                'message' => 'No students were imported. Fix the rows below and upload the file again.',
                'errors' => $result['errors'],
                'skipped' => $result['skipped'],
            ], 422);
        }

        $this->logActivity(
            $request,
            'student.imported',
            "Imported {$result['imported']} student(s) from {$request->file('file')->getClientOriginalName()}.",
            null,
            $branch->id,
        );

        return response()->json([
            'message' => "{$result['imported']} student(s) imported successfully.",
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ], 201);
    }
}
