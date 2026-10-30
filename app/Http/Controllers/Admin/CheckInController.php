<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Edition;
use App\Services\CheckInService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function index()
    {
        return response()->view('admin.check-in', [
            'editions' => Edition::where('is_archived', false)->where('is_published', true)->orderByDesc('starts_at')->get(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function inspect(Request $request, CheckInService $service)
    {
        $data = $this->data($request);
        return $this->result($service->inspect($data['code'], (int) $data['edition_id']));
    }

    public function confirm(Request $request, CheckInService $service)
    {
        $data = $this->data($request);
        return $this->result($service->confirm($data['code'], (int) $data['edition_id'], $request->user()));
    }

    public function attendances(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:150'], 'edition' => ['nullable', 'integer', 'exists:editions,id']]);
        $attendances = Attendance::with(['checker', 'ticket.participation.participant', 'ticket.participation.edition'])
            ->when($filters['edition'] ?? null, fn ($q, $id) => $q->whereHas('ticket.participation', fn ($p) => $p->where('edition_id', $id)))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->whereHas('ticket', fn ($t) => $t
                ->where('reference', 'like', '%' . $search . '%')
                ->orWhereHas('participation.participant', fn ($p) => $p->where('full_name', 'like', '%' . $search . '%'))))
            ->latest('checked_in_at')->paginate(20)->withQueryString();
        return response()->view('admin.attendances', ['attendances' => $attendances, 'editions' => Edition::orderByDesc('starts_at')->get()])
            ->header('Cache-Control', 'private, no-store');
    }

    private function data(Request $request): array
    {
        return $request->validate(['edition_id' => ['required', 'integer', 'exists:editions,id'], 'code' => ['required', 'string', 'max:512']]);
    }

    private function result(array $result)
    {
        $http = $result['http'];
        unset($result['http']);
        return response()->json($result, $http)->header('Cache-Control', 'private, no-store');
    }
}
