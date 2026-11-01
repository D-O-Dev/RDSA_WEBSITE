<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAction extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id'];
    public const LABELS = [
        'maintenance.tests.reset' => 'Données opérationnelles de test effacées',
        'maintenance.initialise' => 'Préparation des données et de l’administrateur définitifs',
        'admin.account.profile' => 'Profil personnel modifié', 'admin.account.sessions.revoke' => 'Autres sessions déconnectées',
        'admin.appearance.update' => 'Apparence de l’édition modifiée',
        'admin.editions.store' => 'Édition créée', 'admin.editions.update' => 'Édition modifiée',
        'admin.participations.review.approve' => 'Réservation validée', 'admin.participations.review.reject' => 'Réservation refusée',
        'admin.participations.destroy' => 'Réservation et billet supprimés définitivement',
        'admin.participations.cancel' => 'Réservation et billet annulés',
        'admin.tickets.issue' => 'Billet mis à disposition', 'admin.check-in.confirm' => 'Entrée validée',
        'admin.media.store' => 'Média ajouté', 'admin.media.update' => 'Média modifié',
        'admin.media.publish' => 'Publication du média modifiée', 'admin.media.destroy' => 'Média retiré',
        'admin.updates.store' => 'Annonce ou FAQ créée', 'admin.updates.update' => 'Annonce ou FAQ modifiée',
        'admin.updates.publish' => 'Publication annonce ou FAQ modifiée', 'admin.updates.destroy' => 'Annonce ou FAQ retirée',
        'admin.calendar.store' => 'Rendez-vous créé', 'admin.calendar.update' => 'Rendez-vous modifié', 'admin.calendar.destroy' => 'Rendez-vous retiré',
        'admin.content.update' => 'Contenu du site modifié',
        'admin.team.store' => 'Compte créé', 'admin.team.update' => 'Compte ou accès modifié', 'admin.account.update' => 'Mot de passe modifié',
        'admin.messages.reply' => 'Réponse à un message envoyée', 'admin.messages.archive' => 'Conversation archivée ou réouverte',
        'admin.whatsapp.update' => 'Configuration WhatsApp modifiée', 'admin.whatsapp.retry' => 'Envoi WhatsApp relancé',
    ];
    public function label(): string
    {
        return self::LABELS[$this->action] ?? 'Action administrative';
    }
}
