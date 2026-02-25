<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureManager($request);

        return view('crm.users.index', [
            'users' => User::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->ensureManager($request);

        return view('crm.users.form', [
            'userRecord' => new User(['role' => 'caller']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager($request);

        $data = $this->validateUser($request);
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return redirect()
            ->route('users.index')
            ->with('status', 'Uzivatel byl vytvoren.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureManager($request);

        return view('crm.users.form', [
            'userRecord' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureManager($request);

        $data = $this->validateUser($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('status', 'Uzivatel byl upraven.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureManager($request);

        if ($request->user()?->id === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('status', 'Nelze smazat vlastni ucet.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('status', 'Uzivatel byl smazan.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $isUpdate = $user !== null;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', 'in:manager,caller'],
            'call_target_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'call_target_until' => ['nullable', 'date'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    private function ensureManager(Request $request): void
    {
        abort_unless($request->user()?->isManager(), 403);
    }
}
