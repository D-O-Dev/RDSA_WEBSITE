<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\MediaItem;
use App\Models\SiteUpdate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrashController extends Controller
{
    private const MODELS = ['media' => MediaItem::class, 'updates' => SiteUpdate::class, 'calendar' => CalendarEvent::class];

    public function index(Request $request)
    {
        $data = $request->validate(['kind' => ['nullable', Rule::in(array_keys(self::MODELS))]]);
        $kind = $data['kind'] ?? 'media';

        return view('admin.trash', ['kind' => $kind, 'items' => self::MODELS[$kind]::onlyTrashed()->latest('deleted_at')->paginate(20)->withQueryString()]);
    }

    public function restore(Request $request, string $kind, int $id)
    {
        abort_unless(isset(self::MODELS[$kind]), 404);
        $record = self::MODELS[$kind]::onlyTrashed()->findOrFail($id);
        if ($kind !== 'calendar') {
            $record->is_published = false;
        }
        $record->restore();
        $request->attributes->set('admin_action_target', $kind.' #'.$id);

        return redirect()->route('admin.trash.index', ['kind' => $kind])->with('status', 'Élément restauré. Les médias, annonces et FAQ restent masqués jusqu’à leur publication.');
    }

    public function destroy(Request $request, string $kind, int $id)
    {
        abort_unless($request->user()->can('manage-sensitive-data'), 403);
        abort_unless(isset(self::MODELS[$kind]), 404);
        $request->validate(['current_password' => ['required', 'current_password:web'], 'confirmation_id' => ['required', Rule::in([(string) $id])]]);
        self::MODELS[$kind]::onlyTrashed()->findOrFail($id)->forceDelete();
        $request->attributes->set('admin_action_target', $kind.' #'.$id);

        return redirect()->route('admin.trash.index', ['kind' => $kind])->with('status', 'Élément effacé définitivement. Les fichiers partagés ne sont pas supprimés automatiquement.');
    }
}
