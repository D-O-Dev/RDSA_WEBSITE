<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\SiteUpdate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteUpdateController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'kind' => ['nullable', Rule::in(['announcement', 'faq'])],
            'q' => ['nullable', 'string', 'max:150'],
            'create' => ['nullable', Rule::in(['announcement', 'faq'])],
            'edit' => ['nullable', 'integer', 'min:1'],
        ]);
        $kind = $filters['kind'] ?? $filters['create'] ?? 'announcement';
        $editing = isset($filters['edit']) ? SiteUpdate::findOrFail($filters['edit']) : null;
        if ($editing) $kind = $editing->kind;
        if (!$editing && isset($filters['create'])) $editing = new SiteUpdate(['kind' => $filters['create'], 'is_published' => false, 'sort_order' => 0]);
        $items = SiteUpdate::with('edition')->where('kind', $kind)
            ->when($filters['q'] ?? null, fn ($query, $text) => $query->where(fn ($q) => $q->where('title', 'like', '%' . $text . '%')->orWhere('body', 'like', '%' . $text . '%')))
            ->orderBy('sort_order')->orderByDesc('id')->paginate(12)->withQueryString();
        return view('admin.updates', ['items' => $items, 'kind' => $kind, 'editing' => $editing, 'editions' => Edition::orderByDesc('starts_at')->get()]);
    }

    public function store(Request $request)
    {
        return $this->save($request, new SiteUpdate);
    }

    public function update(Request $request, SiteUpdate $siteUpdate)
    {
        return $this->save($request, $siteUpdate);
    }

    private function save(Request $request, SiteUpdate $item)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in($item->exists ? [$item->kind] : ['announcement', 'faq'])],
            'title' => ['required', 'string', 'max:150'], 'body' => ['required', 'string', 'max:5000'],
            'edition_id' => ['nullable', 'integer', 'exists:editions,id'],
            'is_published' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'starts_at' => ['exclude_unless:kind,announcement', 'nullable', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['exclude_unless:kind,announcement', 'nullable', 'date_format:Y-m-d\TH:i', ...($request->filled('starts_at') ? ['after:starts_at'] : [])],
        ]);
        // An end date without a start date is allowed: publication begins immediately.
        if ($data['kind'] === 'faq') { $data['starts_at'] = null; $data['ends_at'] = null; }
        $item->fill($data);
        if (!$item->exists) $item->created_by = $request->user()->id;
        $item->save();
        $request->attributes->set('admin_action_target', 'Annonce / FAQ #' . $item->id);
        return redirect()->route('admin.updates.index', ['kind' => $item->kind])->with('status', $item->kind === 'faq' ? 'Question enregistrée.' : 'Annonce enregistrée.');
    }

    public function publish(Request $request, SiteUpdate $siteUpdate)
    {
        $data = $request->validate(['is_published' => ['required', 'boolean']]);
        $siteUpdate->update($data);
        $filters = ['kind' => $siteUpdate->kind];
        if ($request->header('X-RDSA-Navigation') === '1') {
            parse_str(parse_url($request->headers->get('referer', ''), PHP_URL_QUERY) ?? '', $params);
            foreach (['q', 'page'] as $key) if (isset($params[$key]) && is_scalar($params[$key])) $filters[$key] = $params[$key];
        }
        return redirect()->route('admin.updates.index', $filters)->with('status', 'Publication mise à jour.');
    }

    public function destroy(SiteUpdate $siteUpdate)
    {
        $kind = $siteUpdate->kind; $siteUpdate->delete();
        return redirect()->route('admin.updates.index', ['kind' => $kind])->with('status', 'Contenu retiré.');
    }
}
