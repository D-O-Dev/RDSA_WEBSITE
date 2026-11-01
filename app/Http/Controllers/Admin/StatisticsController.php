<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Edition;
use App\Models\Participation;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StatisticsController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->data($request);

        return view('admin.statistics', $data + ['editions' => Edition::orderByDesc('starts_at')->get()]);
    }

    public function export(Request $request)
    {
        $data = $this->data($request);

        return response()->streamDownload(function () use ($data) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Date', 'Demandes reçues', 'Entrées enregistrées'], ';', '"', '');
            foreach ($data['days'] as $day) {
                fputcsv($file, [$day['date'], $day['reservations'], $day['entries']], ';', '"', '');
            }
            fclose($file);
        }, $request->filled('from') || $request->filled('to') ? 'rdsa-statistiques-periode.csv' : 'rdsa-statistiques-30-jours.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function data(Request $request): array
    {
        $filters = $request->validate(['edition' => ['nullable', 'integer', 'exists:editions,id'],
            'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])]]);
        $id = $filters['edition'] ?? null;
        $reservations = Participation::when($id, fn ($q) => $q->where('edition_id', $id));
        $tickets = Ticket::when($id, fn ($q) => $q->whereHas('participation', fn ($p) => $p->where('edition_id', $id)));
        $entries = Attendance::when($id, fn ($q) => $q->whereHas('ticket.participation', fn ($p) => $p->where('edition_id', $id)));
        $statuses = (clone $reservations)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $ticketStatuses = (clone $tickets)->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $total = $statuses->sum();
        $ticketTotal = $ticketStatuses->sum();
        $entered = (clone $entries)->count();
        $eligible = (int) ($ticketStatuses['valid'] ?? 0) + (int) ($ticketStatuses['used'] ?? 0);
        $start = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : today()->subDays(29);
        $last = isset($filters['to']) ? Carbon::parse($filters['to'])->startOfDay() : today();
        if ($last->lessThan($start) || $start->diffInDays($last) > 365) {
            throw ValidationException::withMessages(['to' => 'Choisis une période de 366 jours au maximum, avec une fin après le début.']);
        }
        $end = $last->copy()->addDay();
        $daily = function ($query, string $column) use ($start, $end) {
            return $query->where($column, '>=', $start)->where($column, '<', $end)
                ->selectRaw("DATE($column) as day, COUNT(*) as aggregate")->groupByRaw("DATE($column)")->pluck('aggregate', 'day');
        };
        $received = $daily(clone $reservations, 'created_at');
        $checked = $daily(clone $entries, 'checked_in_at');
        $days = [];
        for ($i = 0; $i <= $start->diffInDays($last); $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $days[] = ['date' => $date, 'reservations' => (int) ($received[$date] ?? 0), 'entries' => (int) ($checked[$date] ?? 0)];
        }

        return compact('statuses', 'ticketStatuses', 'total', 'ticketTotal', 'entered', 'eligible', 'days') +
            ['entryRate' => $eligible ? round($entered / $eligible * 100, 1) : 0];
    }
}
