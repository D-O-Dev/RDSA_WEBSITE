<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\MediaItem;
use App\Models\SiteContent;
use App\Models\SiteUpdate;
use App\Support\AppearanceRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AppearanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['edition' => ['nullable', 'integer', 'exists:editions,id']]);
        $editions = Edition::orderByDesc('is_active')->orderByDesc('starts_at')->get();
        $editing = $request->filled('edition') ? $editions->firstWhere('id', $request->integer('edition')) : $editions->first();
        $images = collect(File::allFiles(public_path('images')))->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))->map(fn ($f) => 'images/' . $f->getRelativePathname())->sort()->values();
        return view('admin.appearance', compact('editions', 'editing', 'images'));
    }
    public function update(Request $request, Edition $edition)
    {
        $data = $request->validate(AppearanceRules::rules());
        DB::transaction(function () use ($data, $edition) {
            $locked = Edition::whereKey($edition->id)->lockForUpdate()->firstOrFail();
            $locked->fill($this->merge($locked, $data))->save();
        });
        return redirect()->route('admin.appearance.index', ['edition' => $edition->id])->with('status', 'Apparence enregistrée pour ' . $edition->name . '.');
    }
    public function preview(Request $request, Edition $edition)
    {
        $data = $request->validate(AppearanceRules::rules());
        $edition = clone $edition;
        $edition->fill($this->merge($edition, $data));
        $updates = SiteUpdate::visibleFor($edition)->orderBy('sort_order')->orderByDesc('id')->get();
        return response()->view('home', [
            'edition' => $edition, 'designPreview' => true,
            'previousEditions' => Edition::where('is_active', false)->where('is_published', true)->orderByDesc('starts_at')->get(),
            'mediaItems' => MediaItem::with('edition')->visible()->orderBy('sort_order')->orderByDesc('id')->get()->map(fn ($item) => $item->publicData())->values(),
            'siteContent' => SiteContent::current(), 'submissionToken' => 'preview-only', 'contactSubmissionToken' => 'preview-only',
            'announcements' => $updates->where('kind', 'announcement')->values(), 'faqs' => $updates->where('kind', 'faq')->values(),
        ])->header('Cache-Control', 'private, no-store')->header('X-RDSA-Preview', '1');
    }
    private function merge(Edition $edition, array $data): array
    {
        $settings = $edition->hero_settings ?? [];
        if (isset($data['hero_settings']['design'])) $data['hero_settings']['design'] = array_replace($settings['design'] ?? [], $data['hero_settings']['design']);
        if (isset($data['hero_settings'])) {
            $data['hero_settings'] = array_replace($settings, $data['hero_settings']);
            if (isset($data['hero_settings']['slides'])) $data['hero_settings']['slides'] = array_values(array_filter($data['hero_settings']['slides']));
        }
        return $data;
    }
}
