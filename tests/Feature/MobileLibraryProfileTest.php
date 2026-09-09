<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLibraryProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_styles_endpoint_returns_per_branch_prefixes(): void
    {
        Branch::factory()->create([
            'name' => 'Hisar',
            'student_code_prefix' => 'HIS',
            'student_code_padding' => 3,
        ]);

        Branch::factory()->create([
            'name' => 'Kurukshetra',
            'student_code_prefix' => 'KKR',
            'student_code_padding' => 3,
        ]);

        $response = $this->getJson('/api/v1/mobile/library/styles');

        $response
            ->assertOk()
            ->assertJsonPath('multi_branch_prefixes', true)
            ->assertJsonCount(2, 'branch_styles')
            ->assertJsonFragment([
                'branch_name' => 'Hisar',
                'prefix' => 'HIS',
                'sample_student_code' => 'HIS-001',
            ])
            ->assertJsonFragment([
                'branch_name' => 'Kurukshetra',
                'prefix' => 'KKR',
                'sample_student_code' => 'KKR-001',
            ]);
    }
}
