<?php

namespace App\Services\Growth;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\LibraryGrowthProfile;
use App\Models\Referral;
use App\Models\Student;
use Closure;
use Illuminate\Support\Facades\Schema;

class ReferralService
{
    private static ?bool $tableReady = null;

    public function tableReady(): bool
    {
        return self::$tableReady ?: (self::$tableReady = Schema::hasTable('referrals'));
    }

    public function findReferrer(?string $code): ?Student
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }

        return Student::query()->whereRaw('UPPER(student_code) = ?', [$code])->first();
    }

    /**
     * Validation rule for an optional "Referred by (Student ID)" field.
     */
    public function codeRule(?string $friendPhone = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($friendPhone): void {
            $referrer = $this->findReferrer((string) $value);
            if (! $referrer) {
                $fail('No student found with this Student ID. Check the ID or leave it blank.');

                return;
            }

            if ($friendPhone !== null && $friendPhone !== '' && $referrer->phone === $friendPhone) {
                $fail('A student cannot refer themselves.');
            }
        };
    }

    public function campaignActive(?int $branchId): bool
    {
        if (! $branchId || ! Schema::hasTable('library_growth_profiles')) {
            return false;
        }

        return LibraryGrowthProfile::query()
            ->where('branch_id', $branchId)
            ->where('referral_campaign_active', true)
            ->exists();
    }

    public function offerText(?int $branchId): ?string
    {
        if (! $this->campaignActive($branchId)) {
            return null;
        }

        return LibraryGrowthProfile::query()->where('branch_id', $branchId)->value('referral_offer_text')
            ?: 'Refer a friend and get a reward';
    }

    public function recordFromEnquiry(Enquiry $enquiry, Student $referrer): ?Referral
    {
        if (! $this->tableReady()) {
            return null;
        }

        return Referral::query()->firstOrCreate(
            ['enquiry_id' => $enquiry->id],
            [
                'branch_id' => $enquiry->branch_id,
                'referrer_student_id' => $referrer->id,
                'referred_name' => $enquiry->name,
                'referred_phone' => $enquiry->phone,
                'status' => Referral::STATUS_ENQUIRY,
                'reward_status' => Referral::REWARD_NOT_DUE,
            ],
        );
    }

    public function recordForNewStudent(Student $student, Student $referrer): ?Referral
    {
        if (! $this->tableReady() || $referrer->id === $student->id) {
            return null;
        }

        return Referral::query()->firstOrCreate(
            ['referred_student_id' => $student->id],
            [
                'branch_id' => $student->branch_id,
                'referrer_student_id' => $referrer->id,
                'referred_name' => $student->name,
                'referred_phone' => $student->phone,
                'status' => Referral::STATUS_JOINED,
                'reward_status' => Referral::REWARD_DUE,
                'joined_at' => now(),
            ],
        );
    }

    public function markEnquiryConverted(Enquiry $enquiry, ?Student $student = null): void
    {
        if (! $this->tableReady()) {
            return;
        }

        Referral::query()
            ->where('enquiry_id', $enquiry->id)
            ->where('status', '!=', Referral::STATUS_JOINED)
            ->update([
                'referred_student_id' => $student?->id,
                'status' => Referral::STATUS_JOINED,
                'reward_status' => Referral::REWARD_DUE,
                'joined_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function joinedRecently(int $branchId, int $days = 90): int
    {
        if (! $this->tableReady()) {
            return 0;
        }

        return Referral::query()
            ->where('branch_id', $branchId)
            ->where('status', Referral::STATUS_JOINED)
            ->where('joined_at', '>=', now()->subDays($days))
            ->count();
    }

    /**
     * @return array{total: int, joined: int, rewards_due: int, rewards_given: int, items: list<array<string, mixed>>}
     */
    public function summary(Branch $branch): array
    {
        $empty = ['total' => 0, 'joined' => 0, 'rewards_due' => 0, 'rewards_given' => 0, 'items' => []];
        if (! $this->tableReady()) {
            return $empty;
        }

        $base = Referral::query()->where('branch_id', $branch->id);

        $items = (clone $base)
            ->with(['referrer:id,name,student_code', 'referredStudent:id,name,student_code'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (Referral $referral) => [
                'id' => $referral->id,
                'referrer_name' => $referral->referrer?->name,
                'referrer_code' => $referral->referrer?->student_code,
                'friend_name' => $referral->referredStudent?->name ?? $referral->referred_name,
                'friend_code' => $referral->referredStudent?->student_code,
                'friend_phone' => $referral->referred_phone,
                'status' => $referral->status,
                'reward_status' => $referral->reward_status,
                'created_at' => $referral->created_at?->format('d M Y'),
                'reward_given_at' => $referral->reward_given_at?->format('d M Y'),
            ])
            ->all();

        return [
            'total' => (clone $base)->count(),
            'joined' => (clone $base)->where('status', Referral::STATUS_JOINED)->count(),
            'rewards_due' => (clone $base)->where('reward_status', Referral::REWARD_DUE)->count(),
            'rewards_given' => (clone $base)->where('reward_status', Referral::REWARD_GIVEN)->count(),
            'items' => $items,
        ];
    }
}
