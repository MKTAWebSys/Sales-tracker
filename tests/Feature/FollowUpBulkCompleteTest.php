<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpBulkCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_multiple_follow_ups_as_done(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => 'Bulk Followup Demo',
            'status' => 'new',
            'assigned_user_id' => $user->id,
        ]);

        $openOne = FollowUp::query()->create([
            'company_id' => $company->id,
            'assigned_user_id' => $user->id,
            'due_at' => now()->addDay(),
            'status' => 'open',
        ]);

        $openTwo = FollowUp::query()->create([
            'company_id' => $company->id,
            'assigned_user_id' => $user->id,
            'due_at' => now()->addDays(2),
            'status' => 'open',
        ]);

        $alreadyDone = FollowUp::query()->create([
            'company_id' => $company->id,
            'assigned_user_id' => $user->id,
            'due_at' => now()->subDay(),
            'status' => 'done',
            'completed_at' => now()->subHours(2),
        ]);

        $this->actingAs($user)
            ->post(route('follow-ups.bulk-complete'), [
                'follow_up_ids' => [$openOne->id, $openTwo->id, $alreadyDone->id],
                'status' => 'open',
            ])
            ->assertRedirect(route('follow-ups.index', ['status' => 'open']));

        $this->assertDatabaseHas('follow_ups', [
            'id' => $openOne->id,
            'status' => 'done',
        ]);

        $this->assertDatabaseHas('follow_ups', [
            'id' => $openTwo->id,
            'status' => 'done',
        ]);

        $this->assertDatabaseHas('follow_ups', [
            'id' => $alreadyDone->id,
            'status' => 'done',
        ]);

        $this->assertNotNull($openOne->fresh()->completed_at);
        $this->assertNotNull($openTwo->fresh()->completed_at);
    }
}
