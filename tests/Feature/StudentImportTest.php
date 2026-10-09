<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PlatformSetting;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        PlatformSetting::query()->create([
            'student_code_prefix' => 'LIB',
            'student_code_padding' => 3,
        ]);

        $this->branch = Branch::factory()->create();
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    public function test_sample_file_downloads_as_xlsx(): void
    {
        $response = $this->actingAs($this->user)->get(route('students.import.template'));

        $response->assertOk();
        $this->assertStringContainsString('student-import-sample.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_valid_rows_are_imported_and_example_rows_skipped(): void
    {
        $file = $this->workbook([
            ['Rohan Gupta', 'male', '2003-05-10', '9876500001', 'rohan@example.com', 'Anil Gupta', 'Delhi', 'Aadhaar', 'regular', 'active'],
            ['Neha Verma', 'Female', '15/02/2005', '+91 98765 00002', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->user)->post(route('students.import.store'), ['file' => $file], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('imported', 2)->assertJsonPath('skipped', 2);
        $this->assertDatabaseHas('students', ['branch_id' => $this->branch->id, 'name' => 'Rohan Gupta', 'phone' => '9876500001']);
        $this->assertDatabaseHas('students', ['name' => 'Neha Verma', 'gender' => 'female', 'phone' => '9876500002', 'status' => 'active', 'student_type' => 'regular']);
        $this->assertSame('2005-02-15', Student::query()->where('name', 'Neha Verma')->value('date_of_birth')?->format('Y-m-d'));
        $this->assertDatabaseMissing('students', ['name' => 'Aarav Sharma (example)']);
    }

    public function test_csv_files_are_imported(): void
    {
        $csv = "Name *,Gender *,Date of Birth *,Phone\nKiran Das,male,2002-11-30,9876500004\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $this->actingAs($this->user)
            ->post(route('students.import.store'), ['file' => $file], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('students', ['name' => 'Kiran Das', 'phone' => '9876500004']);
    }

    public function test_invalid_rows_block_the_whole_import(): void
    {
        $file = $this->workbook([
            ['Valid Person', 'male', '2003-05-10', '9876500001', '', '', '', '', '', ''],
            ['', 'other', '2099-01-01', '12345', 'not-an-email', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->user)->post(route('students.import.store'), ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(422)->assertJsonPath('errors.0.row', 5);
        $this->assertCount(5, $response->json('errors.0.messages'));
        $this->assertSame(0, Student::query()->count());
    }

    public function test_existing_and_duplicate_students_are_reported(): void
    {
        Student::factory()->create(['branch_id' => $this->branch->id, 'name' => 'Rohan Gupta', 'date_of_birth' => '2003-05-10']);

        $file = $this->workbook([
            ['Rohan Gupta', 'male', '2003-05-10', '', '', '', '', '', '', ''],
            ['Sita Rao', 'female', '2004-01-01', '9876500003', '', '', '', '', '', ''],
            ['Sita Rao', 'female', '2004-01-01', '9876500003', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->user)->post(route('students.import.store'), ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(422);
        $this->assertSame([4, 6], array_column($response->json('errors'), 'row'));
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function workbook(array $rows): UploadedFile
    {
        $spreadsheet = app(StudentImportService::class)->templateSpreadsheet();
        $sheet = $spreadsheet->getSheetByName('Students');

        foreach ($rows as $offset => $values) {
            foreach ($values as $index => $value) {
                $sheet->setCellValueExplicit(
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1).($offset + 4),
                    $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
                );
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
