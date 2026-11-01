<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participation;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['after' => ['nullable', 'integer', 'min:0']]);
        $latest = (int) Participation::max('id');
        $items = Participation::with('participant')->where('id', '>', $filters['after'] ?? $latest)->where('id', '<=', $latest)
            ->latest('id')->limit(20)->get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->participant->full_name,
                'url' => route('admin.participations.index', ['q' => $p->reference])]);
        return response()->json(['latest' => $latest, 'pending' => Participation::where('status', 'pending')->count(),
            'messages' => ContactMessage::where('status', 'new')->count(), 'items' => $items])->header('Cache-Control', 'private, no-store');
    }
}
