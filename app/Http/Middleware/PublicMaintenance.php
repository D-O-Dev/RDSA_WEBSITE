<?php

namespace App\Http\Middleware;

use App\Models\ApplicationSetting;
use Closure;
use Illuminate\Http\Request;

class PublicMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('admin', 'admin/*', 'health', 'webhooks/*') && ! $request->user()?->is_admin) {
            $settings = ApplicationSetting::find('maintenance')?->values;
            if ($settings['enabled'] ?? false) {
                return response()->view('maintenance', ['message' => $settings['message']], 503)->header('Retry-After', '3600')->header('Cache-Control', 'no-store');
            }
        }

        return $next($request);
    }
}
