<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $companies = collect([
            [
                'name' => 'Acme Logistics',
                'ico' => '12345678',
                'website' => 'https://acme-logistics.example',
                'status' => 'contacted',
                'notes' => 'Warm lead from referral. Interested in call tracking process.',
            ],
            [
                'name' => 'Northwind Trade',
                'ico' => '87654321',
                'website' => 'https://northwind.example',
                'status' => 'follow-up',
                'notes' => 'Requested callback next week after internal meeting.',
            ],
            [
                'name' => 'BluePeak Services',
                'ico' => null,
                'website' => 'https://bluepeak.example',
                'status' => 'qualified',
                'notes' => 'Decision maker identified. Preparing meeting proposal.',
            ],
        ])->map(function (array $companyData) use ($user) {
            $companyData['assigned_user_id'] = $user->id;

            return Company::query()->updateOrCreate(
                ['name' => $companyData['name']],
                $companyData
            );
        });

        $now = Carbon::now();

        $calls = [
            [
                'company' => 'Acme Logistics',
                'called_at' => $now->copy()->subDays(2)->setTime(10, 30),
                'outcome' => 'interested',
                'summary' => 'Initial discovery call. They want a simple internal CRM for phone outreach.',
                'next_follow_up_at' => $now->copy()->addDay()->setTime(9, 0),
                'meeting_planned_at' => null,
            ],
            [
                'company' => 'Northwind Trade',
                'called_at' => $now->copy()->subDay()->setTime(14, 15),
                'outcome' => 'callback',
                'summary' => 'No final decision yet. Asked for callback after weekly management sync.',
                'next_follow_up_at' => $now->copy()->addDays(3)->setTime(11, 0),
                'meeting_planned_at' => null,
            ],
            [
                'company' => 'BluePeak Services',
                'called_at' => $now->copy()->subHours(6),
                'outcome' => 'meeting-booked',
                'summary' => 'Positive call. Demo meeting agreed for next Tuesday.',
                'next_follow_up_at' => null,
                'meeting_planned_at' => $now->copy()->addDays(5)->setTime(13, 0),
            ],
        ];

        $createdCalls = collect($calls)->map(function (array $callData) use ($companies, $user) {
            $company = $companies->firstWhere('name', $callData['company']);

            return Call::query()->create([
                'company_id' => $company->id,
                'caller_id' => $user->id,
                'handed_over_to_id' => null,
                'called_at' => $callData['called_at'],
                'outcome' => $callData['outcome'],
                'summary' => $callData['summary'],
                'next_follow_up_at' => $callData['next_follow_up_at'],
                'meeting_planned_at' => $callData['meeting_planned_at'],
            ]);
        });

        foreach ($createdCalls as $call) {
            if (! $call->next_follow_up_at) {
                continue;
            }

            FollowUp::query()->create([
                'company_id' => $call->company_id,
                'call_id' => $call->id,
                'assigned_user_id' => $user->id,
                'due_at' => $call->next_follow_up_at,
                'status' => 'open',
                'note' => 'Auto demo follow-up seeded from call outcome: '.$call->outcome,
                'completed_at' => null,
            ]);
        }
    }
}
