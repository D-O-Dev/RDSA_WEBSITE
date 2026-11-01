<?php

namespace App\Http\Middleware;

use App\Models\AdminAction;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecordAdminAction
{
    public function handle(Request $request, Closure $next)
    {
        $action = $request->route()->getName();
        if ($action === 'admin.participations.review') {
            $decision = $request->input('decision');
            if (! in_array($decision, ['approve', 'reject'], true)) {
                return $next($request);
            }
            $action .= '.'.$decision;
        }
        if ($request->isMethod('GET') || ! isset(AdminAction::LABELS[$action])) {
            return $next($request);
        }

        // L'opération et sa trace sont validées ensemble ; aucune donnée de formulaire n'est copiée.
        $errorsBefore = serialize($request->session()->get('errors'));
        $operation = function () use ($request, $next, $action, $errorsBefore) {
            $response = $next($request);
            if ($response->getStatusCode() >= 400 || ($response->exception ?? null) instanceof \Throwable
                || serialize($request->session()->get('errors')) !== $errorsBefore) {
                throw new HttpResponseException($response);
            }
            $target = $request->attributes->get('admin_action_target');
            foreach ($request->route()->parameters() as $key => $value) {
                if (! $target && $value instanceof Model) {
                    $target = (['edition' => 'Édition', 'mediaItem' => 'Média', 'siteUpdate' => 'Annonce / FAQ', 'calendarEvent' => 'Rendez-vous', 'participation' => 'Réservation', 'ticket' => 'Billet', 'user' => 'Compte', 'message' => 'Message'][$key] ?? 'Élément').' #'.$value->getKey();
                    break;
                }
            }
            if ($action === 'admin.check-in.confirm') {
                $data = json_decode($response->getContent(), true);
                if (! ($data['ok'] ?? false)) {
                    return $response;
                }
                $target = $data['reference'] ?? null;
            }
            AdminAction::create(['actor_id' => $request->user()->id, 'actor_name' => $request->user()->name,
                'action' => $action, 'target' => $target, 'reason' => $request->attributes->get('admin_action_reason'), 'details' => $request->attributes->get('admin_action_details'), 'created_at' => now()]);

            return $response;
        };

        // Un appel au fournisseur doit conserver sa propre gestion des envois incertains.
        return $action === 'admin.whatsapp.retry' ? $operation() : DB::transaction($operation);
    }
}
