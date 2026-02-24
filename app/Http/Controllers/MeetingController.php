<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Company;
use App\Models\Meeting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Meeting::query()
            ->with(['company', 'call'])
            ->orderBy('scheduled_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->string('mode'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        return view('crm.meetings.index', [
            'meetings' => $query->paginate(20)->withQueryString(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => (string) $request->input('status', ''),
                'mode' => (string) $request->input('mode', ''),
                'company_id' => (string) $request->input('company_id', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('crm.meetings.form', [
            'meeting' => new Meeting([
                'status' => 'planned',
                'mode' => 'online',
                'scheduled_at' => now()->addDays(2),
                'company_id' => $request->integer('company_id') ?: null,
                'call_id' => $request->integer('call_id') ?: null,
            ]),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $meeting = Meeting::create($this->validateMeeting($request));

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'Meeting created.');
    }

    public function show(Meeting $meeting): View
    {
        $meeting->load(['company', 'call']);

        return view('crm.meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting): View
    {
        return view('crm.meetings.form', [
            'meeting' => $meeting,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'calls' => Call::query()->with('company')->latest('called_at')->limit(100)->get(),
        ]);
    }

    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $meeting->update($this->validateMeeting($request));

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'Meeting updated.');
    }

    private function validateMeeting(Request $request): array
    {
        return $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'call_id' => ['nullable', 'exists:calls,id'],
            'scheduled_at' => ['required', 'date'],
            'mode' => ['required', 'string', 'max:30'],
            'status' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
        ]);
    }
}
