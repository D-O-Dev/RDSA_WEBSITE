# RDSA — site public et administration

Application Laravel 13, Blade, JavaScript, Bootstrap et Vite. PHP 8.4.1 minimum avec le fichier lock actuel.
Les vidéos de la médiathèque utilisent uniquement des liens de plateformes ;
les fichiers vidéo locaux ont été retirés.
Les participations sont gratuites : une personne par réservation, aucun paiement
en ligne. Le site est déployé sur https://rdsa.alwaysdata.net avec une base neuve.
Les éditions et contenus doivent être configurés dans l’administration avant
l’ouverture des inscriptions. Les push Git ne déclenchent pas encore de déploiement automatique.

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
compatible PHP 8.3, HTTPS, persistance des fichiers et restauration d’une sauvegarde.
L’envoi automatique WhatsApp nécessite ses accès réels et un test de livraison.

`php artisan rdsa:check-deployment` vérifie la configuration sans modifier les
données. Aucun hébergement ni domaine n’est configuré automatiquement.
