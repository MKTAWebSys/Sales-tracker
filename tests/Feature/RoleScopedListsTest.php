<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleScopedListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_caller_sees_only_own_companies_even_when_requesting_all(): void
    {
        $caller = User::factory()->create(['role' => 'caller']);
        $other = User::factory()->create(['role' => 'caller']);

        Company::query()->create(['name' => 'Mine Co', 'status' => 'new', 'assigned_user_id' => $caller->id]);
        Company::query()->create(['name' => 'Other Co', 'status' => 'new', 'assigned_user_id' => $other->id]);

        $this->actingAs($caller)
            ->get(route('companies.index', ['mine' => 0]))
            ->assertOk()
            ->assertSee('Mine Co')
            ->assertDontSee('Other Co');
    }

    public function test_caller_sees_only_own_follow_ups_even_when_filtering_other_user(): void
    {
        $caller = User::factory()->create(['role' => 'caller']);
        $other = User::factory()->create(['role' => 'caller']);

        $company = Company::query()->create(['name' => 'Shared Co', 'status' => 'new', 'assigned_user_id' => $caller->id]);

        FollowUp::query()->create([
            'company_id' => $company->id,
            'assigned_user_id' => $caller->id,
            'due_at' => now()->addDay(),
            'status' => 'open',
            'note' => 'Mine followup',
        ]);

        FollowUp::query()->create([
            'company_id' => $company->id,
            'assigned_user_id' => $other->id,
            'due_at' => now()->addDays(2),
            'status' => 'open',
            'note' => 'Other followup',
        ]);

        $this->actingAs($caller)
            ->get(route('follow-ups.index', ['mine' => 0, 'assigned_user_id' => $other->id]))
            ->assertOk()
            ->assertSee('Mine followup')
            ->assertDontSee('Other followup');
    }
}
