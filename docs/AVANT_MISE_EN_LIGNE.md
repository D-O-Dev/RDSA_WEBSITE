# RDSA — exploitation et mises à jour

## Installation en ligne — 1er novembre 2026

- Site public : https://rdsa.alwaysdata.net
- Administration : https://rdsa.alwaysdata.net/admin/login
- Hébergement : alwaysdata, compte `rdsa`, offre Free.
- Application : `/home/rdsa/rdsa` ; racine web : `/home/rdsa/rdsa/public`.
- PHP 8.4 ; le fichier lock actuel exige PHP 8.4.1 minimum.
- Base MySQL : `rdsa_site`, avec un utilisateur dédié limité à cette base.
- HTTPS obligatoire, cookies sécurisés et affichage des erreurs désactivé.

Le propriétaire a choisi une **installation neuve**. Les 30 migrations ont été
appliquées dans la base distante vide. Une nouvelle `APP_KEY` et un nouveau
mot de passe administrateur ont été générés directement sur le serveur.
**Aucun export de la base locale ni aucune ancienne clé n’ont été transférés.**

Le compte en ligne est **Super ADMIN**. Ses identifiants sont dans le fichier
local privé `storage/app/rdsa/private/admin-access-alwaysdata.txt` (droits 600).
Le fichier `admin-access.txt` concerne l’installation locale et son mot de passe
est différent. Aucun de ces fichiers ne doit être ajouté à Git ou à l’archive.

La nouvelle base ne contient aucune édition ni réservation. L’inscription des
participants reste donc fermée jusqu’à la préparation d’une édition active.
Les données conservées dans l’installation locale n’ont pas été effacées.

## Première configuration dans l’admin

1. Se connecter et ouvrir le **Guide d’utilisation** dans le menu. Il présente
   chaque rubrique et se propose à la première visite de ce navigateur.
2. Créer les éditions souhaitées dans **Éditions**. Renseigner leurs vraies
   informations : nom, numéro, thème, date/heure, lieu, carte et contacts.
3. Préparer **Design et apparence** : modèle, images, couleurs, matières,
   typographie, animations. Vérifier les aperçus ordinateur et téléphone avant
   d’enregistrer. Une édition peut être préparée sans ouvrir ses inscriptions.
4. Vérifier **Contenu du site**, les coordonnées et le logo. Remplacer les
   images par défaut et textes de démonstration avant d’annoncer l’ouverture.
5. Compléter **Médiathèque**, **Annonces et FAQ** et les comptes de l’équipe.
   Un contrôleur accède uniquement au contrôle, aux présences et à son compte.
6. Publier et activer l’édition seulement quand ces informations sont prêtes.

Les images peuvent utiliser des liens HTTPS directs et durables. Les liens
vers une publication sociale ne sont pas des liens directs vers une image.
Les vidéos utilisent uniquement des liens YouTube, Facebook, TikTok, Vimeo ou
Instagram : aucun fichier vidéo local n’est livré. Le logo du PDF reste embarqué
localement pour que le document soit autonome.

## Modifier le site au quotidien

**Depuis l’admin** : textes, images, design d’une édition, coordonnées, annonces,
FAQ et médias prennent effet après enregistrement/publication. Aucun push Git
ni nouveau transfert de code n’est nécessaire pour ces modifications.

**Dans le code** : modifier les fichiers locaux, vérifier le résultat, lancer
les tests adaptés et compiler avec `npm ci` puis `npm run build`. Livrer ensuite
le code et `public/build` sur le serveur. Ne pas transférer `node_modules`, les
caches locaux, `public/hot`, les captures de test ou les fichiers privés.

Les données de production sont indépendantes du code : une mise à jour doit
conserver les participants, réservations, billets, médias importés et réglages.

## Déploiement automatique : état actuel

Le dépôt local utilise `git@github.com:D-O-Dev/RDSA_WEBSITE.git`, branche `main`.
**Aucun workflow de déploiement automatique n’est actuellement configuré.**
Un `git push` seul ne met donc pas le site alwaysdata à jour.

La version locale contient encore des modifications à versionner avant de
brancher une automatisation sur le dépôt. Le code actuellement en ligne provient
du paquet préparé depuis l’espace de travail, pas d’un clone du dépôt distant.

Une automatisation GitHub Actions pourra suivre ce parcours :

1. Push sur la branche de production.
2. Installation des dépendances, tests et compilation sur GitHub.
3. Transfert du code validé par SSH, avec une clé dédiée conservée dans les
   secrets GitHub Actions et une empreinte du serveur vérifiée.
4. Application des migrations nécessaires et reconstruction des caches.
5. Contrôle du site ; conservation du `.env` et du stockage de production.

Elle ne doit jamais rejouer l’installation neuve, importer une base locale,
régénérer `APP_KEY` ou lancer `migrate:fresh`. Son activation et l’enregistrement
d’un secret SSH chez GitHub restent une étape distincte à configurer.

Documentation : [secrets GitHub Actions](https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets),
[SSH alwaysdata](https://help.alwaysdata.com/en/docs/web-hosting/remote-access/ssh/).

## Mise à jour manuelle du code

Faire d’abord une sauvegarde de la base et des fichiers persistants, puis
préparer un paquet testé. Pendant une livraison, les commandes suivantes sont
à exécuter depuis `/home/rdsa/rdsa` :

```sh
php artisan down --retry=60
# Transférer le nouveau code et les fichiers compilés sans remplacer .env/storage.
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan rdsa:check-deployment
php artisan up
```

Exécuter ces étapes en contrôlant leur résultat : si une étape échoue, corriger
avant de continuer et de rouvrir le site. Vérifier ensuite `/health`, l’accueil
et la connexion admin. Conserver une version précédente du code pour pouvoir
revenir en arrière ; un retour de code ne restaure pas automatiquement la base.

**À conserver sur le serveur** : `.env` et sa clé, la base, `storage/app/public`,
les sessions et les fichiers privés. Ne jamais livrer le `.env` local à la place
du `.env` en ligne. Ne pas régénérer la clé lors d’une mise à jour.

## Recette avant les inscriptions réelles

- Vérifier les deux interfaces sur téléphone réel, notamment les champs avec
  le clavier ouvert, les fenêtres et les liens des médias et de la carte.
- Faire une réservation de recette : informations, récapitulatif et confirmation.
  Aucun billet utilisable ne doit apparaître avant approbation admin.
- Approuver la demande, télécharger le QR et le PDF, puis tester le contrôle
  caméra en HTTPS et la saisie manuelle. Confirmer l’entrée ; un second passage
  doit être refusé. Le navigateur doit disposer de l’autorisation caméra.
- Tester refus, annulation, récupération privée du billet et droits contrôleur.
- Vérifier sauvegarde et restauration dans une base séparée. Les sauvegardes
  alwaysdata Free sont limitées ; conserver aussi une copie hors hébergement.

Les réservations sont gratuites et individuelles. La suppression définitive
exige une réservation annulée/refusée, sa référence et le mot de passe admin ;
une présence enregistrée bloque cette suppression.

Le partage WhatsApp manuel est disponible. L’envoi automatique exige un compte
fournisseur, ses identifiants, un modèle approuvé et une livraison réelle vérifiée.
Ne pas l’annoncer actif avant ce test. Si activé, planifier `schedule:run` chaque
minute dans alwaysdata. Aucun envoi automatique n’est configuré sur la base neuve.

La cloche admin vérifie les demandes et messages pendant que l’admin est ouvert ;
les annonces publiques se rafraîchissent également sur une page visible.
Ce ne sont pas des notifications système quand le navigateur est fermé.

## Fichiers privés et accès

Les fichiers de `storage/app/rdsa/private` sur l’ordinateur sont exclus de Git
et du paquet. Ils comprennent les accès locaux/en ligne, la clé SSH, le jeton
API et des configurations privées. Ne jamais les déposer dans `public`.
La clé SSH publique finit par `.pub` ; le fichier sans `.pub` reste privé.

La configuration de production utilisée pour l’installation neuve avait une clé
vide ; `key:generate` l’a créée sur le serveur. La configuration locale historique
`production.env` et l’ancien export `database.sql` n’ont pas servi à cette
installation et ne doivent pas être importés ensuite par erreur.

Le contrôle précédent ayant refusé l’import historique, le propriétaire l’a
explicitement abandonné et a choisi l’installation neuve. La sauvegarde locale
avant nettoyage reste dans `storage/app/rdsa/backups/before-reset-20261101-002908`.

Pour conserver le compte alwaysdata Free, respecter les conditions d’usage et
se connecter au panneau d’hébergement au moins tous les 120 jours la première
année. Cela concerne le panneau alwaysdata, pas seulement l’admin RDSA.

## Vérifications du déploiement

Le contrôle `rdsa:check-deployment` a réussi sur le serveur. Les vérifications
HTTP ont confirmé : accueil HTTPS 200, `/health` connecté à la base, connexion
et déconnexion du nouveau Super ADMIN, accès aux pages de son compte et des
réservations. Les chemins `.env`, `composer.json`, journaux et fichiers privés
sont inaccessibles depuis le web.

La vérification navigateur a couvert l’accueil sur ordinateur et mobile,
le contexte sécurisé nécessaire à la caméra, le guide initial, la navigation
admin sans rechargement et les rubriques admin avec une base neuve. Aucune
exception JavaScript n’a été détectée et aucune réservation de démonstration
n’a été créée pendant ces contrôles. Le test de caméra sur un téléphone réel
et le parcours billet complet restent à effectuer après configuration de
l’édition et de ses contenus.

Les fichiers temporaires d’installation et l’archive transférée ont été retirés
du serveur. Le `.env` actif, le stockage de l’application et le rapport privé
d’installation sont conservés. Le nouveau mot de passe administrateur est
accessible dans le fichier privé local indiqué plus haut.
