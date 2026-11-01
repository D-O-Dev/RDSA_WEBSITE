<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UniqueAccountEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where(fn ($q) => $q->where('is_admin', true)->orWhere('team_role', 'controller'));
        $term = trim((string) $request->query('q', ''));
        if ($term !== '') $query->where(fn ($q) => $q->where('name', 'like', '%' . $term . '%')->orWhere('email', 'like', '%' . $term . '%'));
        $editing = $request->filled('edit') ? User::where(fn ($q) => $q->where('is_admin', true)->orWhere('team_role', 'controller'))->findOrFail($request->integer('edit'))
            : ($request->boolean('create') ? new User(['is_active' => true, 'team_role' => 'controller']) : null);
        return view('admin.team', ['members' => $query->orderBy('name')->paginate(15)->withQueryString(), 'editing' => $editing]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new User);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->is_admin || $user->team_role === 'controller', 404);
        return $this->save($request, $user);
    }

    private function save(Request $request, User $user)
    {
        $request->merge(['email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email')]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', new UniqueAccountEmail($user->id)],
            'role' => ['required', Rule::in(['admin', 'controller'])],
            'is_active' => ['required', 'boolean'],
            'current_password' => ['required', 'current_password:web'],
            'password' => [$user->exists ? 'nullable' : 'required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        DB::transaction(function () use ($request, $user, $data) {
            // Serialize role changes so simultaneous requests cannot remove the last admin.
            $admins = User::where('is_admin', true)->orderBy('id')->lockForUpdate()->get();
            $target = $user->exists ? User::whereKey($user->id)->lockForUpdate()->firstOrFail() : $user;
            if ($target->exists && $target->id === $request->user()->id
                && (!$data['is_active'] || $data['role'] !== 'admin')) {
                throw ValidationException::withMessages(['role' => 'Tu ne peux pas retirer ton propre accès administrateur.']);
            }
            if ($target->is_admin && $target->is_active && (!$data['is_active'] || $data['role'] !== 'admin')
                && $admins->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Conserve au moins un administrateur actif.']);
            }
            $changedAccess = $target->exists && ($target->is_admin !== ($data['role'] === 'admin') || $target->is_active !== (bool) $data['is_active']);
            $target->forceFill([
                'name' => $data['name'], 'email' => $data['email'], 'is_admin' => $data['role'] === 'admin',
                'team_role' => $data['role'] === 'controller' ? 'controller' : null, 'is_active' => $data['is_active'],
            ]);
            if (!empty($data['password'])) $target->password = $data['password'];
            if ($changedAccess || !empty($data['password'])) $target->remember_token = null;
            $target->save();
            $request->attributes->set('admin_action_target', 'Compte #' . $target->id);
        });
        return redirect()->route('admin.team.index')->with('status', 'Compte enregistré.');
    }
}
