<?php

namespace Tests\Feature;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\LeadTransfer;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallNextStepFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_storing_call_auto_creates_linked_follow_up_transfer_and_meeting(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();
        $company = Company::query()->create([
            'name' => 'Demo Company',
            'status' => 'new',
            'assigned_user_id' => $caller->id,
        ]);

        $response = $this
            ->actingAs($caller)
            ->post(route('calls.store'), [
                'company_id' => $company->id,
                'called_at' => '2026-02-25 10:00:00',
                'outcome' => 'interested',
                'summary' => 'Interested, wants follow-up and meeting.',
                'next_follow_up_at' => '2026-02-26 09:30:00',
                'meeting_planned_at' => '2026-02-28 14:00:00',
                'handed_over_to_id' => $receiver->id,
            ]);

        $call = Call::query()->firstOrFail();

        $response->assertRedirect(route('calls.show', $call));

        $this->assertDatabaseHas('follow_ups', [
            'call_id' => $call->id,
            'company_id' => $company->id,
            'assigned_user_id' => $receiver->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('lead_transfers', [
            'call_id' => $call->id,
            'company_id' => $company->id,
            'from_user_id' => $caller->id,
            'to_user_id' => $receiver->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('meetings', [
            'call_id' => $call->id,
            'company_id' => $company->id,
            'status' => 'planned',
        ]);
    }

    public function test_updating_call_updates_existing_linked_records_instead_of_creating_duplicates(): void
    {
        $caller = User::factory()->create();
        $receiver = User::factory()->create();
        $company = Company::query()->create([
            'name' => 'Demo Company 2',
            'status' => 'new',
            'assigned_user_id' => $caller->id,
        ]);

        $call = Call::query()->create([
            'company_id' => $company->id,
            'caller_id' => $caller->id,
            'called_at' => '2026-02-25 11:00:00',
            'outcome' => 'callback',
            'next_follow_up_at' => '2026-02-26 10:00:00',
            'meeting_planned_at' => '2026-03-01 10:00:00',
            'handed_over_to_id' => $receiver->id,
        ]);

        FollowUp::query()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'assigned_user_id' => $caller->id,
            'due_at' => '2026-02-26 10:00:00',
            'status' => 'open',
        ]);

        LeadTransfer::query()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'from_user_id' => $caller->id,
            'to_user_id' => $receiver->id,
            'transferred_at' => '2026-02-25 11:00:00',
            'status' => 'pending',
        ]);

        Meeting::query()->create([
            'company_id' => $company->id,
            'call_id' => $call->id,
            'scheduled_at' => '2026-03-01 10:00:00',
            'mode' => 'onsite',
            'status' => 'planned',
        ]);

        $this
            ->actingAs($caller)
            ->put(route('calls.update', $call), [
                'company_id' => $company->id,
                'called_at' => '2026-02-25 11:00:00',
                'outcome' => 'meeting-booked',
                'summary' => 'Updated after confirmation.',
                'next_follow_up_at' => '2026-02-27 10:00:00',
                'meeting_planned_at' => '2026-03-03 15:00:00',
                'handed_over_to_id' => $receiver->id,
            ])
            ->assertRedirect(route('calls.show', $call));

        $this->assertSame(1, FollowUp::query()->where('call_id', $call->id)->count());
        $this->assertSame(1, LeadTransfer::query()->where('call_id', $call->id)->count());
        $this->assertSame(1, Meeting::query()->where('call_id', $call->id)->count());

        $this->assertDatabaseHas('follow_ups', [
            'call_id' => $call->id,
            'due_at' => '2026-02-27 10:00:00',
        ]);

        $this->assertDatabaseHas('meetings', [
            'call_id' => $call->id,
            'scheduled_at' => '2026-03-03 15:00:00',
        ]);
    }
}
