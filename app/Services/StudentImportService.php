<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Student;
use App\Support\ValidationRules;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Throwable;

class StudentImportService
{
    public const MAX_ROWS = 500;

    public const EXAMPLE_SUFFIX = '(example)';

    /**
     * Column key => [header label, required, help text].
     *
     * @var array<string, array{0: string, 1: bool, 2: string}>
     */
    public const COLUMNS = [
        'name' => ['Name', true, 'Full name of the student.'],
        'gender' => ['Gender', true, 'male or female'],
        'date_of_birth' => ['Date of Birth', true, 'YYYY-MM-DD, e.g. 2004-08-15. Must be in the past.'],
        'phone' => ['Phone', false, '10-digit mobile number starting with 6, 7, 8 or 9.'],
        'email' => ['Email', false, 'Valid email address.'],
        'father_name' => ['Father Name', false, 'Father or guardian name.'],
        'address' => ['Address', false, 'Full address (max 1000 characters).'],
        'id_proof_type' => ['ID Proof Type', false, 'e.g. Aadhaar, PAN, Driving Licence.'],
        'student_type' => ['Student Type', false, 'regular or trial (default: regular).'],
        'status' => ['Status', false, 'active or inactive (default: active).'],
    ];

    public function __construct(
        private StudentCreator $studentCreator,
        private StudentCodeService $studentCodeService,
    ) {}

    public function templateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Students');

        $keys = array_keys(self::COLUMNS);
        $lastColumn = $this->columnLetter(count($keys));

        foreach ($keys as $index => $key) {
            [$label, $required] = self::COLUMNS[$key];
            $sheet->setCellValue($this->columnLetter($index + 1).'1', $label.($required ? ' *' : ''));
        }

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '243A8B']],
        ]);
        $sheet->freezePane('A2');

        $examples = [
            ['Aarav Sharma '.self::EXAMPLE_SUFFIX, 'male', '2004-08-15', '9876543210', 'aarav@example.com', 'Rajesh Sharma', '12 MG Road, Jaipur', 'Aadhaar', 'regular', 'active'],
            ['Diya Patel '.self::EXAMPLE_SUFFIX, 'female', '2006-01-22', '9123456780', '', 'Mahesh Patel', '45 Station Road, Surat', 'PAN', 'trial', 'active'],
        ];

        foreach ($examples as $rowOffset => $values) {
            foreach ($values as $index => $value) {
                $sheet->setCellValueExplicit(
                    $this->columnLetter($index + 1).($rowOffset + 2),
                    $value,
                    \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
                );
            }
        }

        $sheet->getStyle("A2:{$lastColumn}3")->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '64748B']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
        ]);

        $sheet->getStyle('C2:D'.(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $this->addListValidation($sheet, 'B', ['male', 'female']);
        $this->addListValidation($sheet, 'I', ['regular', 'trial']);
        $this->addListValidation($sheet, 'J', ['active', 'inactive']);

        foreach (range(1, count($keys)) as $index) {
            $sheet->getColumnDimension($this->columnLetter($index))->setAutoSize(true);
        }

        $help = $spreadsheet->createSheet();
        $help->setTitle('Instructions');
        $help->fromArray([
            ['How to import students'],
            ['1. Fill one student per row in the "Students" sheet. Keep the header row as it is.'],
            ['2. Columns marked * are required.'],
            ['3. The two grey rows ending with "'.self::EXAMPLE_SUFFIX.'" are examples and are skipped automatically. You can delete them.'],
            ['4. Upload up to '.self::MAX_ROWS.' students at a time (.xlsx, .xls or .csv).'],
            ['5. If any row has an error, nothing is imported — fix the listed rows and upload again.'],
            [''],
            ['Column', 'Required', 'Format'],
        ]);

        $row = 9;
        foreach (self::COLUMNS as [$label, $required, $hint]) {
            $help->fromArray([[$label, $required ? 'Yes' : 'No', $hint]], null, "A{$row}");
            $row++;
        }

        $help->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $help->getStyle('A8:C8')->getFont()->setBold(true);
        $help->getColumnDimension('A')->setWidth(18);
        $help->getColumnDimension('B')->setWidth(10);
        $help->getColumnDimension('C')->setWidth(60);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<array{row: int, messages: list<string>}>}
     */
    public function import(Branch $branch, UploadedFile $file): array
    {
        abort_unless(
            $this->studentCodeService->prefixIsConfigured(),
            422,
            'Set the global student code prefix in Settings before importing students.',
        );

        $rows = $this->readRows($file);

        if ($rows === []) {
            abort(422, 'The file has no student rows. Fill the "Students" sheet and try again.');
        }

        if (count($rows) > self::MAX_ROWS) {
            abort(422, 'You can import up to '.self::MAX_ROWS.' students at a time. This file has '.count($rows).'.');
        }

        $errors = [];
        $valid = [];
        $skipped = 0;
        $seen = [];

        foreach ($rows as $rowNumber => $data) {
            if (str_ends_with(mb_strtolower($data['name'] ?? ''), self::EXAMPLE_SUFFIX)) {
                $skipped++;

                continue;
            }

            $validator = Validator::make($data, $this->rules(), [], $this->attributeNames());

            if ($branch->require_student_contact) {
                $validator->after(function ($validator) use ($data) {
                    if (($data['phone'] ?? '') === '') {
                        $validator->errors()->add('phone', 'Phone is required for this branch.');
                    }
                    if (($data['email'] ?? '') === '') {
                        $validator->errors()->add('email', 'Email is required for this branch.');
                    }
                });
            }

            if ($validator->fails()) {
                $errors[] = ['row' => $rowNumber, 'messages' => $validator->errors()->all()];

                continue;
            }

            $clean = array_map(static fn ($value) => $value === '' ? null : $value, $validator->validated());
            $fingerprint = mb_strtolower($clean['name']).'|'.$clean['date_of_birth'].'|'.($clean['phone'] ?? '');

            if (isset($seen[$fingerprint])) {
                $errors[] = ['row' => $rowNumber, 'messages' => ["Duplicate of row {$seen[$fingerprint]} in this file."]];

                continue;
            }

            if ($this->alreadyExists($branch, $clean)) {
                $errors[] = ['row' => $rowNumber, 'messages' => ["{$clean['name']} ({$clean['date_of_birth']}) already exists in this branch."]];

                continue;
            }

            $seen[$fingerprint] = $rowNumber;
            $valid[] = $clean;
        }

        if ($errors !== []) {
            return ['imported' => 0, 'skipped' => $skipped, 'errors' => $errors];
        }

        if ($valid === []) {
            abort(422, 'The file only contains example rows. Add your students below the header and try again.');
        }

        DB::transaction(function () use ($branch, $valid) {
            foreach ($valid as $data) {
                $this->studentCreator->create($branch, $data);
            }
        });

        return ['imported' => count($valid), 'skipped' => $skipped, 'errors' => []];
    }

    /**
     * @return array<int, array<string, string>> Keyed by spreadsheet row number.
     */
    private function readRows(UploadedFile $file): array
    {
        try {
            $type = match (strtolower($file->getClientOriginalExtension())) {
                'xlsx' => 'Xlsx',
                'xls' => 'Xls',
                'csv', 'txt' => 'Csv',
                default => null,
            };
            $reader = $type ? IOFactory::createReader($type) : IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (Throwable) {
            abort(422, 'Could not read the file. Upload the sample .xlsx file (or a .csv) filled with your students.');
        }

        $sheet = $spreadsheet->getSheetByName('Students') ?? $spreadsheet->getSheet(0);
        $grid = $sheet->toArray(null, false, false, false);

        if ($grid === []) {
            return [];
        }

        $columnMap = $this->mapHeaders(array_shift($grid));

        foreach (['name', 'gender', 'date_of_birth'] as $required) {
            if (! in_array($required, $columnMap, true)) {
                abort(422, 'Missing column "'.self::COLUMNS[$required][0].'". Download the sample file and keep its header row.');
            }
        }

        $rows = [];

        foreach ($grid as $offset => $cells) {
            $data = [];

            foreach ($columnMap as $index => $key) {
                $data[$key] = $this->normalizeCell($key, $cells[$index] ?? null);
            }

            if (implode('', $data) === '') {
                continue;
            }

            $rows[$offset + 2] = $data;
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<int, string>
     */
    private function mapHeaders(array $headers): array
    {
        $lookup = [];
        foreach (self::COLUMNS as $key => [$label]) {
            $lookup[$this->headerSlug($label)] = $key;
            $lookup[$this->headerSlug($key)] = $key;
        }
        $lookup['dob'] = 'date_of_birth';
        $lookup['mobile'] = 'phone';
        $lookup['guardianname'] = 'father_name';
        $lookup['type'] = 'student_type';

        $map = [];
        foreach ($headers as $index => $header) {
            $slug = $this->headerSlug((string) $header);
            if (isset($lookup[$slug]) && ! in_array($lookup[$slug], $map, true)) {
                $map[$index] = $lookup[$slug];
            }
        }

        return $map;
    }

    private function headerSlug(string $value): string
    {
        return preg_replace('/[^a-z]/', '', strtolower($value)) ?? '';
    }

    private function normalizeCell(string $key, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($key === 'date_of_birth') {
            return $this->normalizeDate($value);
        }

        if ($key === 'phone') {
            $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

            return strlen($digits) === 12 && str_starts_with($digits, '91') ? substr($digits, 2) : (strlen($digits) === 11 && str_starts_with($digits, '0') ? substr($digits, 1) : $digits);
        }

        $string = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        if (in_array($key, ['gender', 'student_type', 'status'], true)) {
            return strtolower($string);
        }

        if ($key === 'email') {
            return strtolower($string);
        }

        return $string;
    }

    private function normalizeDate(mixed $value): string
    {
        if (is_numeric($value) && (float) $value > 59 && (float) $value < 2958465) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $string = trim((string) $value);

        if ($string === '') {
            return '';
        }

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'd.m.Y', 'Y/m/d', 'j/n/Y', 'j-n-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $string);
            if ($date !== false && $date->format($format) === $string) {
                return $date->format('Y-m-d');
            }
        }

        return $string;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today'],
            'phone' => ValidationRules::phoneOptional(),
            'email' => ValidationRules::emailOptional(),
            'father_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'id_proof_type' => ['nullable', 'string', 'max:100'],
            'student_type' => ['nullable', Rule::in(['regular', 'trial'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return array_map(static fn (array $column) => $column[0], self::COLUMNS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function alreadyExists(Branch $branch, array $data): bool
    {
        return Student::query()
            ->where('branch_id', $branch->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->exists();
    }

    /**
     * @param  list<string>  $options
     */
    private function addListValidation(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $column, array $options): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid value')
            ->setError('Choose one of: '.implode(', ', $options))
            ->setFormula1('"'.implode(',', $options).'"');

        $sheet->setDataValidation("{$column}2:{$column}".(self::MAX_ROWS + 1), $validation);
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
