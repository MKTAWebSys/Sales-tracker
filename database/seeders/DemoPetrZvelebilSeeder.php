<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\Company;
use App\Models\FollowUp;
use App\Models\LeadTransfer;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoPetrZvelebilSeeder extends Seeder
{
    public function run(): void
    {
        $petr = User::query()->updateOrCreate(
            ['email' => 'petr.zvelebil@awebsys.cz'],
            [
                'name' => 'Petr Zvelebil',
                'role' => 'manager',
                'call_target_count' => 30,
                'call_target_until' => now()->addDays(14)->toDateString(),
            ]
        );

        if (! $petr->password) {
            $petr->forceFill([
                'password' => Hash::make('password'),
            ])->save();
        }

        $otherCaller = User::query()->firstOrCreate(
            ['email' => 'ivana.caller@example.com'],
            [
                'name' => 'Ivana Caller',
                'password' => Hash::make('password'),
                'role' => 'caller',
            ]
        );

        $companySpecs = [
            ['name' => 'Awebsys Demo Lead Alpha', 'status' => 'follow-up', 'ico' => '10000001', 'website' => 'https://alpha.demo.example'],
            ['name' => 'Awebsys Demo Lead Beta', 'status' => 'qualified', 'ico' => '10000002', 'website' => 'https://beta.demo.example'],
            ['name' => 'Awebsys Demo Lead Gamma', 'status' => 'contacted', 'ico' => '10000003', 'website' => null],
            ['name' => 'Awebsys Demo Lead Delta', 'status' => 'new', 'ico' => '10000004', 'website' => null],
        ];

        $companies = collect($companySpecs)->map(function (array $spec) use ($petr) {
            return Company::query()->firstOrCreate(
                ['name' => $spec['name']],
                [
                    'ico' => $spec['ico'],
                    'website' => $spec['website'],
                    'status' => $spec['status'],
                    'notes' => 'Demo data for Petr Zvelebil workload.',
                    'assigned_user_id' => $petr->id,
                ]
            );
        })->values();

        $base = Carbon::now()->startOfDay()->subDays(3);

        foreach ($companies as $idx => $company) {
            $callA = Call::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'called_at' => (clone $base)->addDays($idx)->setTime(9 + $idx, 15),
                ],
                [
                    'caller_id' => $petr->id,
                    'outcome' => ['callback', 'interested', 'no-answer', 'meeting-booked'][$idx % 4],
                    'summary' => 'Demo call created for Petr workload.',
                    'next_follow_up_at' => (clone $base)->addDays($idx + 1)->setTime(10, 0),
                    'meeting_planned_at' => $idx % 2 === 1 ? (clone $base)->addDays($idx + 2)->setTime(14, 0) : null,
                    'handed_over_to_id' => $idx % 3 === 0 ? $otherCaller->id : null,
                ]
            );

            FollowUp::query()->firstOrCreate(
                ['call_id' => $callA->id],
                [
                    'company_id' => $company->id,
                    'assigned_user_id' => $idx % 2 === 0 ? $petr->id : $otherCaller->id,
                    'due_at' => $idx === 0
                        ? Carbon::now()->subDay()->setTime(11, 0)
                        : ($idx === 1 ? Carbon::now()->setTime(15, 0) : Carbon::now()->addDays($idx)->setTime(10, 30)),
                    'status' => $idx === 2 ? 'done' : 'open',
                    'note' => 'Demo follow-up for Petr.',
                    'completed_at' => $idx === 2 ? Carbon::now()->subHours(5) : null,
                ]
            );

            if ($callA->handed_over_to_id) {
                LeadTransfer::query()->firstOrCreate(
                    ['call_id' => $callA->id],
                    [
                        'company_id' => $company->id,
                        'from_user_id' => $petr->id,
                        'to_user_id' => $otherCaller->id,
                        'transferred_at' => $callA->called_at,
                        'status' => 'accepted',
                        'note' => 'Demo handover from Petr.',
                    ]
                );
            }

            if ($callA->meeting_planned_at) {
                Meeting::query()->firstOrCreate(
                    ['call_id' => $callA->id],
                    [
                        'company_id' => $company->id,
                        'scheduled_at' => $callA->meeting_planned_at,
                        'mode' => $idx % 2 === 0 ? 'online' : 'onsite',
                        'status' => 'planned',
                        'note' => 'Demo meeting linked to Petr call.',
                    ]
                );
            }
        }

        $this->seedDashboardDrilldownData($petr, $otherCaller);
    }

    private function seedDashboardDrilldownData(User $petr, User $otherCaller): void
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        $extraCompanies = collect([
            ['name' => 'Awebsys Dashboard Today 1', 'status' => 'follow-up'],
            ['name' => 'Awebsys Dashboard Today 2', 'status' => 'follow-up'],
            ['name' => 'Awebsys Dashboard Overdue 1', 'status' => 'follow-up'],
            ['name' => 'Awebsys Dashboard Overdue 2', 'status' => 'contacted'],
            ['name' => 'Awebsys Dashboard Pending Call', 'status' => 'contacted'],
            ['name' => 'Awebsys Dashboard Meeting Planned', 'status' => 'qualified'],
        ])->map(function (array $spec) use ($petr) {
            return Company::query()->firstOrCreate(
                ['name' => $spec['name']],
                [
                    'status' => $spec['status'],
                    'notes' => 'Dashboard drilldown demo data.',
                    'assigned_user_id' => $petr->id,
                ]
            );
        })->keyBy('name');

        $today1Call = Call::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Today 1']->id,
                'called_at' => $today->copy()->subDay()->setTime(10, 15),
            ],
            [
                'caller_id' => $petr->id,
                'outcome' => 'callback',
                'summary' => 'Demo call for follow-up due today (Petr).',
                'next_follow_up_at' => $today->copy()->setTime(11, 30),
            ]
        );

        $today2Call = Call::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Today 2']->id,
                'called_at' => $today->copy()->subDay()->setTime(13, 0),
            ],
            [
                'caller_id' => $petr->id,
                'outcome' => 'interested',
                'summary' => 'Second demo call for today queue.',
                'next_follow_up_at' => $today->copy()->setTime(15, 0),
                'meeting_planned_at' => $today->copy()->addDays(2)->setTime(9, 30),
            ]
        );

        $overdue1Call = Call::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Overdue 1']->id,
                'called_at' => $today->copy()->subDays(3)->setTime(9, 45),
            ],
            [
                'caller_id' => $petr->id,
                'outcome' => 'callback',
                'summary' => 'Demo overdue follow-up for Petr.',
                'next_follow_up_at' => $today->copy()->subDays(1)->setTime(10, 0),
            ]
        );

        $overdue2Call = Call::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Overdue 2']->id,
                'called_at' => $today->copy()->subDays(4)->setTime(14, 20),
            ],
            [
                'caller_id' => $otherCaller->id,
                'outcome' => 'callback',
                'summary' => 'Demo overdue follow-up assigned to another caller.',
                'next_follow_up_at' => $today->copy()->subDays(2)->setTime(16, 0),
                'handed_over_to_id' => $petr->id,
            ]
        );

        $pendingCall = Call::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Pending Call']->id,
                'called_at' => $now->copy()->subMinutes(8)->second(0),
            ],
            [
                'caller_id' => $petr->id,
                'outcome' => 'pending',
                'summary' => null,
            ]
        );

        $meetingCall = Call::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Meeting Planned']->id,
                'called_at' => $today->copy()->subDay()->setTime(11, 10),
            ],
            [
                'caller_id' => $petr->id,
                'outcome' => 'meeting-booked',
                'summary' => 'Demo call for planned meeting metric.',
                'meeting_planned_at' => $today->copy()->addDay()->setTime(13, 0),
            ]
        );

        FollowUp::query()->firstOrCreate(
            ['call_id' => $today1Call->id],
            [
                'company_id' => $today1Call->company_id,
                'assigned_user_id' => $petr->id,
                'due_at' => $today->copy()->setTime(11, 30),
                'status' => 'open',
                'note' => 'Dashboard demo: due today (Petr).',
            ]
        );

        FollowUp::query()->firstOrCreate(
            ['call_id' => $today2Call->id],
            [
                'company_id' => $today2Call->company_id,
                'assigned_user_id' => $otherCaller->id,
                'due_at' => $today->copy()->setTime(15, 0),
                'status' => 'open',
                'note' => 'Dashboard demo: due today (other caller).',
            ]
        );

        FollowUp::query()->firstOrCreate(
            ['call_id' => $overdue1Call->id],
            [
                'company_id' => $overdue1Call->company_id,
                'assigned_user_id' => $petr->id,
                'due_at' => $today->copy()->subDay()->setTime(10, 0),
                'status' => 'open',
                'note' => 'Dashboard demo: overdue (Petr).',
            ]
        );

        FollowUp::query()->firstOrCreate(
            ['call_id' => $overdue2Call->id],
            [
                'company_id' => $overdue2Call->company_id,
                'assigned_user_id' => $petr->id,
                'due_at' => $today->copy()->subDays(2)->setTime(16, 0),
                'status' => 'open',
                'note' => 'Dashboard demo: overdue after handover.',
            ]
        );

        LeadTransfer::query()->firstOrCreate(
            ['call_id' => $overdue2Call->id],
            [
                'company_id' => $overdue2Call->company_id,
                'from_user_id' => $otherCaller->id,
                'to_user_id' => $petr->id,
                'transferred_at' => $overdue2Call->called_at,
                'status' => 'accepted',
                'note' => 'Dashboard demo handover to Petr.',
            ]
        );

        Meeting::query()->firstOrCreate(
            ['call_id' => $today2Call->id],
            [
                'company_id' => $today2Call->company_id,
                'scheduled_at' => $today->copy()->addDays(2)->setTime(9, 30),
                'mode' => 'online',
                'status' => 'planned',
                'note' => 'Dashboard demo planned meeting #1.',
            ]
        );

        Meeting::query()->firstOrCreate(
            ['call_id' => $meetingCall->id],
            [
                'company_id' => $meetingCall->company_id,
                'scheduled_at' => $today->copy()->addDay()->setTime(13, 0),
                'mode' => 'onsite',
                'status' => 'planned',
                'note' => 'Dashboard demo planned meeting #2.',
            ]
        );

        // One confirmed meeting so "planned" filter remains visibly distinct from all meetings list.
        Meeting::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Meeting Planned']->id,
                'scheduled_at' => $today->copy()->addDays(3)->setTime(10, 0),
            ],
            [
                'call_id' => null,
                'mode' => 'phone',
                'status' => 'confirmed',
                'note' => 'Dashboard demo confirmed meeting.',
            ]
        );

        // Extra done follow-up to keep open counts realistic vs total history.
        FollowUp::query()->firstOrCreate(
            [
                'company_id' => $extraCompanies['Awebsys Dashboard Today 1']->id,
                'due_at' => $today->copy()->subDays(4)->setTime(9, 0),
            ],
            [
                'call_id' => null,
                'assigned_user_id' => $petr->id,
                'status' => 'done',
                'note' => 'Older completed follow-up for list variety.',
                'completed_at' => $today->copy()->subDays(4)->setTime(9, 20),
            ]
        );
    }
}
