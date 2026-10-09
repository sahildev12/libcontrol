<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\GrowthOrder;
use App\Models\LibraryGrowthProfile;
use App\Models\PlatformSetting;
use App\Models\Referral;
use App\Models\Student;
use App\Services\StudentCodeService;
use Illuminate\Database\Seeder;

/**
 * Sample referrals and growth requests for previewing Grow My Library.
 * Safe to run more than once: records are matched by name.
 */
class GrowthReferralDemoSeeder extends Seeder
{
    public function run(StudentCodeService $codes): void
    {
        $branch = Branch::query()->orderBy('id')->first();
        if (! $branch) {
            $this->command?->warn('Create a branch first.');

            return;
        }

        LibraryGrowthProfile::query()->updateOrCreate(
            ['branch_id' => $branch->id],
            ['referral_campaign_active' => true, 'referral_offer_text' => 'Refer a friend & get ₹200 off'],
        );

        $student = function (string $name, string $phone, int $daysAgo = 30) use ($branch, $codes): Student {
            return Student::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $name],
                [
                    'student_code' => $codes->generate($branch),
                    'phone' => $phone,
                    'gender' => 'female',
                    'status' => 'active',
                    'student_type' => Student::TYPE_REGULAR,
                    'created_at' => now()->subDays($daysAgo),
                ],
            );
        };

        $riya = $student('Riya Sharma', '9811000001', 120);
        $karan = $student('Karan Malhotra', '9811000002', 90);
        $neha = $student('Neha Gupta', '9811000003', 60);

        $rows = [
            // [referrer, friend name, friend phone, days ago, state]
            [$riya, 'Vikas Yadav', '9822000001', 40, 'given'],
            [$riya, 'Simran Kaur', '9822000002', 12, 'due'],
            [$karan, 'Aditya Rao', '9822000003', 6, 'due'],
            [$neha, 'Pooja Singh', '9822000004', 3, 'enquiry'],
            [$karan, 'Rahul Jain', '9822000005', 1, 'enquiry'],
        ];

        foreach ($rows as [$referrer, $name, $phone, $daysAgo, $state]) {
            $at = now()->subDays($daysAgo);
            $joined = $state !== 'enquiry';

            $friend = $joined ? $student($name, $phone, $daysAgo) : null;

            $enquiry = Enquiry::query()->firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $name],
                [
                    'phone' => $phone,
                    'message' => 'Looking for a full-day seat.',
                    'status' => $joined ? 'converted' : 'new',
                    'student_id' => $friend?->id,
                    'created_at' => $at,
                ],
            );

            Referral::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'referred_name' => $name],
                [
                    'referrer_student_id' => $referrer->id,
                    'enquiry_id' => $enquiry->id,
                    'referred_student_id' => $friend?->id,
                    'referred_phone' => $phone,
                    'status' => $joined ? Referral::STATUS_JOINED : Referral::STATUS_ENQUIRY,
                    'reward_status' => match ($state) {
                        'given' => Referral::REWARD_GIVEN,
                        'due' => Referral::REWARD_DUE,
                        default => Referral::REWARD_NOT_DUE,
                    },
                    'joined_at' => $joined ? $at->copy()->addDay() : null,
                    'reward_given_at' => $state === 'given' ? $at->copy()->addDays(5) : null,
                    'created_at' => $at,
                ],
            );
        }

        $orders = [
            ['package', 'starter', 'Starter', GrowthOrder::STATUS_ACTIVE, 299900, 20],
            ['service', 'social_marketing', 'Facebook & Instagram Marketing', GrowthOrder::STATUS_QUOTED, null, 8],
            ['service', 'video_reels', 'Video / Reels Marketing', GrowthOrder::STATUS_COMPLETED, null, 35],
        ];

        $settings = PlatformSetting::current();

        foreach ($orders as [$type, $key, $name, $status, $amount, $daysAgo]) {
            GrowthOrder::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'item_key' => $key, 'message' => 'Demo request'],
                [
                    'order_type' => $type,
                    'item_name' => $name,
                    'status' => $status,
                    'priority' => 'normal',
                    'contact_name' => 'Client Admin',
                    'contact_email' => 'admin@gmail.com',
                    'contact_phone' => $branch->phone,
                    'library_name' => $settings->displayName(),
                    'library_code' => $settings->library_code,
                    'deployment_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
                    'amount_paise' => $amount,
                    'created_at' => now()->subDays($daysAgo),
                ],
            );
        }

        $this->command?->info('Demo referrals and growth requests added for '.$branch->name.'.');
    }
}
