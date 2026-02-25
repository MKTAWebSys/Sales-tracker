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

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('call_id')) {
            $query->where('call_id', $request->integer('call_id'));
        }

        return view('crm.meetings.index', [
            'meetings' => $query->paginate(20)->withQueryString(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => (string) $request->input('status', ''),
                'company_id' => (string) $request->input('company_id', ''),
                'call_id' => (string) $request->input('call_id', ''),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('crm.meetings.form', [
            'meeting' => new Meeting([
                'status' => 'planned',
                'mode' => 'onsite',
                'scheduled_at' => now()->addDay(),
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
            ->with('status', 'Schůzka byla vytvořena.');
    }

    public function show(Meeting $meeting): View
    {
        $meeting->load(['company', 'call.company']);

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
            ->with('status', 'Schůzka byla upravena.');
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
