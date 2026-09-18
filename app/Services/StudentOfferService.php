<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentOfferService
{
    public function __construct(
        private StudentEmailNotificationService $notifications,
        private MailDeliveryService $mailDelivery,
    ) {}

    public function offersEnabled(): bool
    {
        return (bool) PlatformSetting::current()->email_offers_enabled;
    }

    /**
     * @return Collection<int, Student>
     */
    public function eligibleStudents(?int $branchId, ?array $studentIds = null): Collection
    {
        $query = $this->baseQuery($branchId);

        if (is_array($studentIds) && $studentIds !== []) {
            $query->whereIn('id', $studentIds);
        }

        return $query
            ->with(['familyGroup:id,email', 'branch:id,name'])
            ->orderBy('name')
            ->get()
            ->filter(fn (Student $student) => filled($student->effectiveEmail()))
            ->values();
    }

    /**
     * @return array{sent: int, skipped: int, total: int}
     */
    public function sendOffers(
        ?int $branchId,
        string $subject,
        string $body,
        string $audience = 'all',
        ?array $studentIds = null,
        ?string $actionUrl = null,
    ): array {
        if (! $this->offersEnabled()) {
            throw ValidationException::withMessages([
                'offers' => 'Offer emails are disabled. Enable them in Settings → Emails.',
            ]);
        }

        if ($issue = $this->mailDelivery->issue()) {
            throw ValidationException::withMessages([
                'mail' => $issue,
            ]);
        }

        $message = trim($body);
        $link = trim((string) $actionUrl);

        if ($link !== '') {
            $message .= "\n\n".$link;
        }

        $students = $audience === 'selected'
            ? $this->eligibleStudents($branchId, $studentIds)
            : $this->eligibleStudents($branchId);

        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => 'No students with an email address match your selection.',
            ]);
        }

        $sent = 0;
        $skipped = 0;

        foreach ($students as $student) {
            if ($this->notifications->sendOffer($student, $subject, $message)) {
                $sent++;
            } else {
                $skipped++;
            }
        }

        if ($sent === 0) {
            throw ValidationException::withMessages([
                'mail' => 'No offer emails could be delivered. Check your SMTP settings and try again.',
            ]);
        }

        return [
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $skipped,
            'total' => $students->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function serializeStudentsForPicker(?int $branchId): array
    {
        return $this->eligibleStudents($branchId)
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'name' => $student->name,
                'email' => $student->effectiveEmail(),
                'branch_name' => $student->branch?->name,
            ])
            ->values()
            ->all();
    }

    private function baseQuery(?int $branchId): Builder
    {
        return Student::query()
            ->where('status', 'active')
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }
}
