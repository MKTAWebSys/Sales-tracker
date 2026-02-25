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

class DemoCrmSeeder extends Seeder
{
    public function run(): void
    {
        if (Company::query()->count() > 0 || Call::query()->count() > 0) {
            return;
        }

        $users = collect([
            ['name' => 'Jan Novak', 'email' => 'jan.sales@example.com', 'role' => 'caller'],
            ['name' => 'Petra Svobodova', 'email' => 'petra.sales@example.com', 'role' => 'caller'],
            ['name' => 'Marek Dvorak', 'email' => 'marek.sales@example.com', 'role' => 'manager'],
        ])->map(function (array $user) {
            return User::query()->firstOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'password' => Hash::make('password'), 'role' => $user['role']]
            );
        })->values();

        $companies = collect([
            ['name' => 'Alfa Stroj s.r.o.', 'status' => 'contacted', 'ico' => '28100412', 'website' => 'https://www.alfastroj.cz'],
            ['name' => 'Beta Logistics a.s.', 'status' => 'follow-up', 'ico' => '45211873', 'website' => 'https://www.betalogistics.cz'],
            ['name' => 'Clever Energy s.r.o.', 'status' => 'qualified', 'ico' => '27340188', 'website' => 'https://www.cleverenergy.cz'],
            ['name' => 'Delta Facility Group', 'status' => 'new', 'ico' => null, 'website' => 'https://www.deltafacility.example'],
            ['name' => 'EkoServis Morava', 'status' => 'contacted', 'ico' => '60455290', 'website' => null],
            ['name' => 'Futura Retail CZ', 'status' => 'lost', 'ico' => '29133714', 'website' => 'https://www.futuraretail.cz'],
            ['name' => 'Gama Tech Systems', 'status' => 'follow-up', 'ico' => '06751234', 'website' => 'https://www.gamatech.example'],
            ['name' => 'Helios Industry', 'status' => 'qualified', 'ico' => '48392011', 'website' => null],
        ])->map(function (array $companyData, int $idx) use ($users) {
            return Company::query()->create([
                'name' => $companyData['name'],
                'ico' => $companyData['ico'],
                'website' => $companyData['website'],
                'status' => $companyData['status'],
                'notes' => 'Demo lead for CRM flow testing.',
                'assigned_user_id' => $users[$idx % $users->count()]->id,
            ]);
        })->values();

        $outcomes = ['no-answer', 'callback', 'interested', 'not-interested', 'meeting-booked'];
        $callCounter = 0;

        foreach ($companies as $companyIndex => $company) {
            $callsPerCompany = 2 + ($companyIndex % 3);

            for ($i = 0; $i < $callsPerCompany; $i++) {
                $calledAt = Carbon::now()->subDays(18 - ($companyIndex * 2) - $i)->setTime(9 + (($companyIndex + $i) % 7), [0, 15, 30, 45][($companyIndex + $i) % 4]);
                $caller = $users[($companyIndex + $i) % $users->count()];
                $handoverTo = (($i + $companyIndex) % 4 === 0) ? $users[($companyIndex + $i + 1) % $users->count()] : null;
                $outcome = $outcomes[$callCounter % count($outcomes)];

                $call = Call::query()->create([
                    'company_id' => $company->id,
                    'caller_id' => $caller->id,
                    'handed_over_to_id' => $handoverTo?->id,
                    'called_at' => $calledAt,
                    'outcome' => $outcome,
                    'summary' => $this->summaryFor($company->name, $outcome),
                    'next_follow_up_at' => in_array($outcome, ['callback', 'interested'], true)
                        ? (clone $calledAt)->addDays(($i % 3) + 1)
                        : null,
                    'meeting_planned_at' => in_array($outcome, ['interested', 'meeting-booked'], true)
                        ? (clone $calledAt)->addDays(($i % 4) + 2)->setTime(14, 0)
                        : null,
                ]);

                if ($call->next_follow_up_at) {
                    FollowUp::query()->create([
                        'company_id' => $company->id,
                        'call_id' => $call->id,
                        'assigned_user_id' => $handoverTo?->id ?? $caller->id,
                        'due_at' => $call->next_follow_up_at,
                        'status' => $call->next_follow_up_at->isPast() ? 'done' : 'open',
                        'note' => 'Demo follow-up generated for call outcome '.$outcome.'.',
                        'completed_at' => $call->next_follow_up_at->isPast() ? (clone $call->next_follow_up_at)->addHours(2) : null,
                    ]);
                }

                if ($handoverTo) {
                    LeadTransfer::query()->create([
                        'company_id' => $company->id,
                        'call_id' => $call->id,
                        'from_user_id' => $caller->id,
                        'to_user_id' => $handoverTo->id,
                        'transferred_at' => $calledAt,
                        'status' => $calledAt->isPast() ? 'accepted' : 'pending',
                        'note' => 'Demo handover after first qualification step.',
                    ]);
                }

                if ($call->meeting_planned_at) {
                    Meeting::query()->create([
                        'company_id' => $company->id,
                        'call_id' => $call->id,
                        'scheduled_at' => $call->meeting_planned_at,
                        'mode' => ['onsite', 'online', 'phone'][($companyIndex + $i) % 3],
                        'status' => $call->meeting_planned_at->isPast() ? 'confirmed' : 'planned',
                        'note' => 'Demo meeting scheduled from call.',
                    ]);
                }

                $callCounter++;
            }
        }
    }

    private function summaryFor(string $companyName, string $outcome): string
    {
        return match ($outcome) {
            'no-answer' => "No one answered at {$companyName}. Tried main line and reception.",
            'callback' => "Reached reception at {$companyName}. Requested callback tomorrow morning.",
            'interested' => "{$companyName} showed initial interest and asked for more details by phone.",
            'not-interested' => "{$companyName} is not interested at the moment due to current supplier contract.",
            'meeting-booked' => "Meeting booked with {$companyName}; contact expects short discovery call first.",
            default => "Call logged for {$companyName}.",
        };
    }
}
