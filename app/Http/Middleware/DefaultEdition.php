<?php

namespace App\Http\Middleware;

use App\Models\Edition;
use Closure;
use Illuminate\Http\Request;

class DefaultEdition
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->routeIs('admin.participations.index', 'admin.tickets.index', 'admin.attendances.index', 'admin.statistics.*', 'admin.dashboard', 'admin.exports') && ! $request->query->has('edition')) {
            $id = Edition::where('is_active', true)->where('is_published', true)->where('is_archived', false)->orderByDesc('starts_at')->value('id');
            if ($id) {
                $request->query->set('edition', (string) $id);
            }
        }

        return $next($request);
    }
}
