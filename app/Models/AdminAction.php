<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAction extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['details' => 'array'];

    public const LABELS = [
        'maintenance.tests.reset' => 'Données opérationnelles de test effacées',
        'maintenance.initialise' => 'Préparation des données et de l’administrateur définitifs',
        'admin.account.profile' => 'Profil personnel modifié', 'admin.account.sessions.revoke' => 'Autres sessions déconnectées',
        'admin.two-factor.enable' => 'Double authentification activée', 'admin.two-factor.disable' => 'Double authentification désactivée',
        'admin.appearance.update' => 'Apparence de l’édition modifiée',
        'admin.editions.store' => 'Édition créée', 'admin.editions.update' => 'Édition modifiée',
        'admin.editions.duplicate' => 'Édition dupliquée en brouillon', 'admin.editions.destroy' => 'Brouillon d’édition supprimé',
        'admin.participations.review.approve' => 'Réservation validée', 'admin.participations.review.reject' => 'Réservation refusée',
        'admin.participations.destroy' => 'Réservation et billet supprimés définitivement',
        'admin.participations.cancel' => 'Réservation et billet annulés',
        'admin.participations.correct' => 'Informations du participant corrigées',
        'admin.participations.store' => 'Réservation créée par l’équipe',
        'admin.participations.reopen' => 'Demande remise en attente',
        'admin.participations.replace-ticket' => 'Billet remplacé et anciens accès révoqués',
        'admin.participations.undo-entry' => 'Entrée annulée par le Super ADMIN',
        'admin.participations.purge-test' => 'Réservation de test, billet et présence supprimés par le Super ADMIN',
        'admin.tickets.issue' => 'Billet mis à disposition', 'admin.check-in.confirm' => 'Entrée validée',
        'admin.media.store' => 'Média ajouté', 'admin.media.update' => 'Média modifié',
        'admin.media.publish' => 'Publication du média modifiée', 'admin.media.destroy' => 'Média retiré',
        'admin.updates.store' => 'Annonce ou FAQ créée', 'admin.updates.update' => 'Annonce ou FAQ modifiée',
        'admin.updates.publish' => 'Publication annonce ou FAQ modifiée', 'admin.updates.destroy' => 'Annonce ou FAQ retirée',
        'admin.calendar.store' => 'Rendez-vous créé', 'admin.calendar.update' => 'Rendez-vous modifié', 'admin.calendar.destroy' => 'Rendez-vous retiré',
        'admin.content.update' => 'Contenu du site modifié',
        'admin.team.store' => 'Compte créé', 'admin.team.update' => 'Compte ou accès modifié', 'admin.account.update' => 'Mot de passe modifié',
        'admin.messages.reply' => 'Réponse à un message envoyée', 'admin.messages.archive' => 'Conversation archivée ou réouverte',
        'admin.messages.destroy' => 'Conversation supprimée définitivement', 'admin.team.destroy' => 'Compte désactivé supprimé',
        'admin.trash.restore' => 'Élément restauré depuis la corbeille', 'admin.trash.destroy' => 'Élément effacé de la corbeille',
        'admin.storage.destroy' => 'Fichiers inutilisés nettoyés', 'admin.media.photos' => 'Photos ajoutées en groupe',
        'admin.bulk' => 'Opération groupée confirmée',
        'admin.backups.store' => 'Sauvegarde privée créée', 'admin.backups.restore' => 'Données du site restaurées',
        'admin.backups.maintenance' => 'Maintenance publique modifiée', 'admin.backups.schedule' => 'Sauvegarde quotidienne configurée',
        'maintenance.backup' => 'Sauvegarde quotidienne créée',
        'admin.backups.import' => 'Archive de sauvegarde importée', 'admin.backups.destroy' => 'Archive privée supprimée',
        'admin.whatsapp.update' => 'Configuration WhatsApp modifiée', 'admin.whatsapp.retry' => 'Envoi WhatsApp relancé',
        'admin.mail.update' => 'Configuration des e-mails modifiée',
        'admin.whatsapp.resolve' => 'Envoi WhatsApp incertain vérifié manuellement',
    ];

    public function label(): string
    {
        return self::LABELS[$this->action] ?? 'Action administrative';
    }
}
