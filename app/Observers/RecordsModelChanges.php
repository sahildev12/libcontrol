<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class RecordsModelChanges
{
    /**
     * @var list<string>
     */
    private array $ignore = [
        'id',
        'created_at',
        'updated_at',
        'remember_token',
        'email_verified_at',
        'expiry_reminder_sent_at',
        'cancelled_at',
        'cancellation_reason',
        'trial_start',
        'trial_end',
    ];

    public function updated(Model $model): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        if ($user->isDeveloperAdmin() && config('database.default') === 'tenant') {
            return;
        }

        $changes = $this->changedFields($model);

        if ($changes === []) {
            return;
        }

        $name = $this->subjectName($model);
        $label = $this->subjectLabel($model);

        if (count($changes) === 1) {
            $change = $changes[0];
            $description = "Changed {$change['label']} from {$change['from']} to {$change['to']}".($name ? " on {$name}" : '').'.';
        } else {
            $description = 'Updated '.$label.($name ? " {$name}" : '').' ('.count($changes).' changes).';
        }

        app(ActivityLogger::class)->record(
            $user,
            Str::snake(class_basename($model)).'.updated',
            $description,
            $model,
            $this->branchId($model, $user),
            ['changes' => $changes],
            request(),
        );
    }

    /**
     * @return list<array{label: string, from: string, to: string}>
     */
    private function changedFields(Model $model): array
    {
        $changes = [];

        foreach (array_keys($model->getChanges()) as $key) {
            if (in_array($key, $this->ignore, true)) {
                continue;
            }

            $oldValue = $model->getOriginal($key);
            $newValue = $model->getAttribute($key);

            if ($this->sameValue($oldValue, $newValue)) {
                continue;
            }

            $changes[] = [
                'label' => $this->fieldLabel($key),
                'from' => $this->displayValue($key, $oldValue),
                'to' => $this->displayValue($key, $newValue),
            ];
        }

        return $changes;
    }

    private function sameValue(mixed $old, mixed $new): bool
    {
        return $this->comparable($old) === $this->comparable($new);
    }

    private function comparable(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateTimeString();
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return (string) json_encode($value);
        }

        return (string) $value;
    }

    private function displayValue(string $key, mixed $value): string
    {
        if (in_array($key, ['password'], true)) {
            return $value ? 'hidden' : 'empty';
        }

        if (str_ends_with($key, '_path')) {
            return $value ? 'uploaded file' : 'none';
        }

        if ($value instanceof Carbon) {
            return $value->format('d M Y');
        }

        if (in_array($key, ['joining_date', 'plan_expiry_date', 'due_date', 'paid_at', 'date_of_birth'], true) && filled($value)) {
            try {
                return Carbon::parse($value)->format('d M Y');
            } catch (\Throwable) {
                // fall through
            }
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $value = $this->listText($value);
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if ($value === null || $value === '') {
            return 'empty';
        }

        if (is_numeric($value) && str_contains($key, 'amount')) {
            return '₹'.$value;
        }

        if (! is_scalar($value)) {
            return Str::limit((string) json_encode($value), 80, '…');
        }

        $text = (string) $value;

        return Str::limit(str_contains($text, '://') ? $text : str_replace('_', ' ', $text), 80, '…');
    }

    /**
     * @param  array<mixed>  $value
     */
    private function listText(array $value): string
    {
        $parts = [];

        foreach ($value as $itemKey => $item) {
            if (is_array($item)) {
                $item = $item['path'] ?? $item['name'] ?? null;
            }

            if (! is_scalar($item) || (string) $item === '') {
                continue;
            }

            $parts[] = is_string($itemKey) ? ucfirst($itemKey).': '.$item : (string) $item;
        }

        return implode(', ', $parts);
    }

    private function fieldLabel(string $key): string
    {
        return match ($key) {
            'display_name' => 'Library name',
            'name' => 'Name',
            'fee_amount' => 'Fee amount',
            'fee_type' => 'Fee type',
            'joining_date' => 'Joining date',
            'plan_expiry_date' => 'Plan end date',
            'student_code_prefix' => 'Student ID letters',
            'student_code_padding' => 'Student ID digits',
            'library_open_time' => 'Opening time',
            'library_close_time' => 'Closing time',
            'is_open_24_hours' => 'Open 24 hours',
            'expiry_reminder_days' => 'Reminder days',
            'seat_capacity' => 'Seat capacity',
            'logo_with_text_path', 'simple_logo_path', 'logo_path' => 'Logo',
            'favicon_path' => 'Small icon',
            'photo_path' => 'Photo',
            'paid_at' => 'Paid on',
            'payment_plan' => 'Payment plan',
            'installment_frequency' => 'Installment frequency',
            'fee_paid_at' => 'Paid on',
            'due_date' => 'Due date',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }

    private function subjectLabel(Model $model): string
    {
        return match (class_basename($model)) {
            'Branch' => 'library',
            'Hall' => 'hall',
            'Student' => 'student',
            'Enquiry' => 'enquiry',
            'SeatBooking' => 'fee / seat plan',
            'PlatformSetting' => 'library settings',
            'User' => 'profile',
            'FeeInstallment' => 'installment',
            default => strtolower(class_basename($model)),
        };
    }

    private function subjectName(Model $model): string
    {
        foreach (['name', 'display_name', 'title'] as $field) {
            if (filled($model->getAttribute($field))) {
                return (string) $model->getAttribute($field);
            }
        }

        if ($model->getAttribute('student_code')) {
            return (string) $model->getAttribute('student_code');
        }

        if (class_basename($model) === 'SeatBooking') {
            $name = $model->student?->name;

            return $name ? (string) $name : '';
        }

        if (class_basename($model) === 'FeeInstallment' && $model->getAttribute('installment_number')) {
            $student = $model->booking?->student?->name;

            return trim('installment '.$model->getAttribute('installment_number').($student ? " for {$student}" : ''));
        }

        return '';
    }

    private function branchId(Model $model, $user): ?int
    {
        if ($model->getAttribute('branch_id')) {
            return (int) $model->getAttribute('branch_id');
        }

        if (class_basename($model) === 'SeatBooking') {
            $branchId = $model->seat?->hall?->branch_id;

            return $branchId ? (int) $branchId : ($user->branch_id ? (int) $user->branch_id : null);
        }

        if (class_basename($model) === 'FeeInstallment') {
            $branchId = $model->booking?->seat?->hall?->branch_id;

            return $branchId ? (int) $branchId : ($user->branch_id ? (int) $user->branch_id : null);
        }

        return $user->branch_id ? (int) $user->branch_id : null;
    }
}
