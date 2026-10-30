<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Edition;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m', 'regex:/^(19|20|21)\d{2}-(0[1-9]|1[0-2])$/'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before:2200-01-01'],
            'edit' => ['nullable', 'integer', 'min:1'],
            'create' => ['nullable', 'boolean'],
        ]);
        $month = isset($filters['month']) ? CarbonImmutable::createFromFormat('!Y-m', $filters['month']) : CarbonImmutable::now()->startOfMonth();
        $end = $month->addMonth();
        $editions = Edition::where('starts_at', '>=', $month)->where('starts_at', '<', $end)->orderBy('starts_at')->orderBy('id')->get();
        $events = CalendarEvent::where('starts_at', '>=', $month)->where('starts_at', '<', $end)->orderBy('starts_at')->orderBy('id')->get();
        $agenda = $editions->map(fn ($edition) => [
            'kind' => 'edition', 'title' => $edition->name, 'starts_at' => $edition->starts_at,
            'location' => $edition->venue, 'notes' => null,
            'state' => $edition->is_archived ? 'Archivée' : (!$edition->is_published ? 'Brouillon' : ($edition->is_active ? 'Inscriptions ouvertes' : 'Inscriptions fermées')),
            'url' => route('admin.index', ['edit' => $edition->id]),
        ])->concat($events->map(fn ($event) => [
            'kind' => 'event', 'title' => $event->title, 'starts_at' => $event->starts_at,
            'location' => $event->location, 'notes' => $event->notes, 'state' => 'Rendez-vous interne',
            'url' => route('admin.calendar.index', ['month' => $month->format('Y-m'), 'edit' => $event->id]),
        ]))->sortBy(fn ($item) => $item['starts_at']->format('Y-m-d H:i:s'))->values()->groupBy(fn ($item) => $item['starts_at']->format('Y-m-d'));
        $start = $month->startOfWeek();
        $days = collect(range(0, 41))->map(fn ($offset) => $start->addDays($offset));
        $editing = isset($filters['edit']) ? CalendarEvent::findOrFail($filters['edit']) : null;
        if (!$editing && $request->boolean('create')) {
            $date = $filters['date'] ?? ($month->isSameMonth(CarbonImmutable::now()) ? CarbonImmutable::today()->format('Y-m-d') : $month->format('Y-m-d'));
            $editing = new CalendarEvent(['starts_at' => $date . ' 09:00:00']);
        }
        return view('admin.calendar', compact('month', 'days', 'agenda', 'editions', 'events', 'editing'));
    }

    public function store(Request $request)
    {
        return $this->save($request, new CalendarEvent);
    }

    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        return $this->save($request, $calendarEvent);
    }

    private function save(Request $request, CalendarEvent $event)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:1900-01-01', 'before:2200-01-01'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $event->fill($data);
        if (!$event->exists) $event->created_by = $request->user()->id;
        $event->save();
        return redirect()->route('admin.calendar.index', ['month' => $event->starts_at->format('Y-m')])->with('status', 'Rendez-vous enregistré.');
    }

    public function destroy(CalendarEvent $calendarEvent)
    {
        $month = $calendarEvent->starts_at->format('Y-m');
        $calendarEvent->delete();
        return redirect()->route('admin.calendar.index', ['month' => $month])->with('status', 'Rendez-vous retiré du calendrier.');
    }
}
