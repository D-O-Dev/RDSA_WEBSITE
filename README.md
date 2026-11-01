# RDSA — site public et administration

Application Laravel 13, Blade, JavaScript, Bootstrap et Vite. PHP 8.4.1 minimum avec le fichier lock actuel.
Les vidéos de la médiathèque utilisent uniquement des liens de plateformes ;
les fichiers vidéo locaux ont été retirés.
Les participations sont gratuites : une personne par réservation, aucun paiement
en ligne. Le site est déployé sur https://rdsa.alwaysdata.net avec une base neuve.
Les éditions 1 et 2 sont disponibles dans l’historique et RDSA 3 est ouverte
aux réservations ; les contenus et visuels restent modifiables dans l’administration. Le workflow GitHub Actions prépare le déploiement continu sur `main` : tests,
compilation, sauvegarde puis mise à jour du serveur. La clé dédiée
`ALWAYSDATA_SSH_KEY` est configurée et un premier déploiement complet a réussi.

## Parcours des billets

1. Le visiteur renseigne ses informations, vérifie le récapitulatif puis confirme.
2. Sa demande reste en attente ; aucun billet ni QR n’est émis avant approbation.
3. L’admin approuve ou refuse la demande. L’approbation émet le billet individuel.
4. Le visiteur retrouve le billet, enregistre son QR en PNG, télécharge le PDF
   ou partage le lien par WhatsApp. La référence et le téléphone/code privé
   permettent de le retrouver depuis « Mon billet ».
5. À l’entrée, l’équipe lit le QR par caméra, photo locale ou référence, puis
   confirme le passage. Un second passage est refusé.

L’envoi WhatsApp automatique est facultatif. Il nécessite des identifiants Meta,
un modèle approuvé, une adresse HTTPS publique et une livraison réelle vérifiée.
Le partage manuel reste disponible sans activer cette intégration.

## Administration

Accès direct : `/admin`, sans lien nécessaire sur le site public. La connexion et
les droits sont contrôlés côté serveur. Un contrôleur dispose uniquement des
fonctions d’entrée, de présence et de son compte ; les autres outils sont réservés
aux administrateurs.

- Tableau de bord, statistiques par édition, export agrégé et journal des actions.
- Réservations : approbation, refus, annulation et suppression définitive protégée.
- Billets, contrôles d’entrée, registre des présences et suivi WhatsApp.
- Calendrier des éditions et préparatifs internes.
- Éditions, médiathèque, textes du site, annonces, FAQ et conversations privées.
- Guide d’utilisation par rubrique, adapté aux administrateurs et contrôleurs.
- Comptes et accès de l’équipe ; profil personnel, adresse de connexion, mot de
  passe et déconnexion des autres appareils dans **Mon compte**.
- **Design et apparence** : sélection d’une édition existante, choix du modèle,
  images, couleurs, matières, animation et aperçu ordinateur/téléphone avant
  enregistrement. L’aperçu n’enregistre rien et ne permet pas de réserver.

La navigation interne conserve la sidebar, les brouillons et les validations sans
rechargement complet. Les fonctions restent accessibles avec leurs routes Laravel.

## Design et apparence

La rubrique dédiée propose **RDSA original** et neuf compositions adaptées aux
références fournies : **Élan**, **Atelier**, **Horizon**, **Sanctuaire**,
**Espérance**, **Nuage**, **Lilas**, **Immersion** et **Voyage**. Les miniatures des
références servent au choix dans l’admin ; les images du site restent celles de
l’édition. Les modèles adaptent aussi les sections, cartes, formulaires et fenêtres.

Les réglages se font depuis `/admin/appearance`, indépendamment de la création
ou de la modification des informations de l’édition. Une édition en brouillon
peut être préparée sans la publier ni modifier ses dates, lieux ou inscriptions.
Les anciens choix enregistrés restent interprétables, sans encombrer la galerie.

Les adresses d’images HTTPS sont validées ; elles ne sont pas téléchargées par le
serveur. L’image de premier plan est facultative (photo ou portrait transparent).
Les aperçus d’apparence sont privés et les styles HTML/CSS arbitraires sont refusés.
Le QR conserve son champ blanc et le PDF sa présentation imprimable.

## Alertes et confidentialité

La cloche admin examine les inscriptions et messages quand la page est visible.
Les annonces publiques disposent d’une alerte, d’un compteur et de points rouges
jusqu’au marquage explicite comme lues. L’état est conservé pendant la session du
navigateur et une annonce modifiée redevient non lue. Aucun push hors navigateur
n’est annoncé comme actif.

Les recherches de billets et conversations privées sont protégées. Les pages
privées sont `no-store`, `noindex` et ne transmettent pas leur URL comme référent.
Les formulaires utilisent CSRF, validations serveur et limitations de fréquence.
Les réservations possèdent un contrôle de doublon et des protections anti-abus.

## Retraits et suppressions

Les médias, annonces, FAQ et rendez-vous se retirent depuis l’admin. Les éditions
et conversations s’archivent ; les comptes se désactivent. Une réservation annulée
ou refusée peut être supprimée définitivement avec référence exacte et mot de
passe admin. Une présence enregistrée, un envoi WhatsApp en cours/incertain ou un
ancien historique financier bloque cet effacement. Les fichiers partagés et les
documents déjà reçus chez un destinataire ne sont pas rappelés.

## Développement et vérifications

```bash
composer install
npm ci
php artisan migrate
npm run dev
php artisan serve
```

```bash
php artisan test
npm run build
git diff --check
composer audit
npm audit
```

Les tests fonctionnels utilisent une base SQLite distincte en mémoire. Les essais
navigateur doivent également utiliser des données fictives séparées. Les captures,
billets et bases de démonstration ne font pas partie du déploiement.

Le modèle HTML `../rdsa-admin.html` reste une référence de conception, hors de la
racine web Laravel. Le code en production est celui de cette application.

## Avant la mise en ligne

Consulter [la procédure de préparation](docs/AVANT_MISE_EN_LIGNE.md).
Les points externes restent à vérifier : vrais liens et images, recette du design
sur les deux interfaces, caméra/clavier sur téléphone réel, hébergement gratuit
compatible PHP 8.4.1 minimum, HTTPS, persistance des fichiers et restauration d’une sauvegarde.
L’envoi automatique WhatsApp nécessite ses accès réels et un test de livraison.

`php artisan rdsa:check-deployment` vérifie la configuration sans modifier les
données. Aucun hébergement ni domaine n’est configuré automatiquement.

## Gestion V2

La V2 complète l’administration existante sans remplacer Laravel, Blade ou le design.
Le premier administrateur actif devient Super ADMIN lors de la migration ; les
créations ultérieures de comptes restent soumises aux permissions du propriétaire.

- **Réservations → Gérer / corriger** : corriger les coordonnées, réexaminer une
  demande refusée/annulée, remplacer un billet compromis, annuler une entrée
  erronée ou supprimer entièrement un test, présence comprise. Les actions
  exceptionnelles exigent le rôle Super ADMIN, la référence, le mot de passe et
  un motif inscrit au journal. Aucun test réel n’est supprimé automatiquement. Changer le téléphone révoque les anciens liens privés et remplace les billets encore utilisables.
- Listes filtrées par défaut sur l’édition active ; « Toutes les éditions » reste
  disponible. Exports CSV nominatifs filtrés et traités par lots de 250 lignes ;
  aucun jeton de billet n’est exporté. Sélection et actions groupées sur les
  réservations et médias, limitées à 100 éléments explicitement confirmés.
- Éditions duplicables en brouillon ; suppression d’un brouillon uniquement s’il
  n’a aucune donnée associée. Dates d’ouverture/fermeture et capacité facultatives.
  Les contrôles sont effectués côté serveur, sous verrou lors de l’inscription.
- Corbeille pour les médias, annonces/FAQ et rendez-vous ; restauration masquée,
  puis publication explicite. Conversations désarchivables/supprimables et
  suppression protégée des comptes désactivés hors Super ADMIN.
- Ajout groupé de photos, images de marque téléversables, nettoyage des images
  inutilisées de plus de 24 heures. La corbeille protège les fichiers référencés.
  La galerie publique ne charge que 60 médias par page.
- Rubriques publiques masquables, ordre et libellés du menu modifiables,
  coordonnées permanentes, titre/description de recherche et logo PDF dédiés.
  Les images externes ne sont pas téléchargées par le serveur ; pour le PDF,
  utiliser une image locale/téléversée, en JPG si GD n’est pas disponible.
- **Mon compte** : double authentification TOTP facultative et codes de secours
  à usage unique, en complément du mot de passe. Les secrets sont chiffrés et
  les codes déjà utilisés sont refusés. Implémentation vérifiée avec les vecteurs
  de la [RFC 6238](https://www.rfc-editor.org/rfc/rfc6238).
- **Envoi des e-mails** : accès SMTP chiffrés, réservés au Super ADMIN, pour le
  parcours « Mot de passe oublié ». Un véritable service d’envoi est nécessaire ;
  aucun fournisseur n’est activé automatiquement.
- Notifications Web Push facultatives via la bibliothèque PHP
  [minishlink/web-push](https://github.com/web-push-libs/web-push-php). Un clic et
  l’autorisation du navigateur sont nécessaires sur chaque appareil. Les alertes
  admin n’incluent pas les coordonnées des participants. Le service worker ne
  met aucune page privée en cache. La réception dépend du navigateur et du système.
- Suivi central WhatsApp et résolution manuelle des envois incertains. Une
  résolution ne renvoie aucun message ; la relance est une action distincte.
- **Sauvegardes et maintenance** : archives privées chiffrées pour les données
  métier et jusqu’à 100 Mo d’images téléversées, téléchargement/import, restauration
  avec sauvegarde préalable et maintenance publique laissant l’admin accessible.
  Les comptes, clés d’hébergement, code, journal et paramètres d’infrastructure
  ne sont pas remplacés. Les envois WhatsApp restaurés qui pourraient avoir déjà
  été exécutés sont bloqués pour vérification manuelle.

### Automatisations

Le planificateur Laravel doit tourner chaque minute sur l’hébergement :

```sh
cd /home/rdsa/rdsa && php artisan schedule:run --no-interaction
```

Il traite les commandes `rdsa:deliver-tickets`, `rdsa:deliver-push` et
`rdsa:backup-site`. La sauvegarde quotidienne reste désactivée jusqu’au choix
explicite de son horaire dans l’admin ; les trois dernières archives automatiques
sont conservées. L’écran affiche le dernier passage du planificateur.
Les archives manuelles restent conservées jusqu’à suppression explicite.
