<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['actor' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', Rule::in(array_keys(AdminAction::LABELS))],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])]]);
        $actions = AdminAction::when($filters['actor'] ?? null, fn ($q, $id) => $q->where('actor_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $date . ' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->where('created_at', '<', \Carbon\Carbon::parse($date)->addDay()))
            ->latest('id')->paginate(25)->withQueryString();
        return view('admin.audit', ['actions' => $actions, 'actors' => User::orderBy('name')->get(), 'labels' => AdminAction::LABELS]);
    }
}
