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

Après l’installation neuve, les éditions réelles ont été renseignées depuis
l’administration sur confirmation du propriétaire. RDSA 3 est ouverte pour le
13 février 2027 à 8 h, à Yopougon Micao. Les éditions 1 et 2 sont publiées comme
historique, avec inscriptions fermées, pour permettre leur sélection dans le
formulaire. Aucune réservation de test n’a été envoyée pendant cette configuration.
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

## Déploiement continu GitHub Actions

Le dépôt utilise `git@github.com:D-O-Dev/RDSA_WEBSITE.git`, branche `main`.
Le workflow `.github/workflows/deploy.yml` contrôle les push et les pull requests
vers `main`. Seuls les push sur `main` et les lancements manuels depuis `main`
peuvent livrer en production. Une pull request exécute les tests sans accès à la
clé de déploiement.

1. GitHub installe PHP 8.4 et Node 22, les dépendances verrouillées et compile
   les fichiers publics. Il exécute les tests PHP et les essais des scripts de
   déploiement dans des dossiers temporaires, sans toucher à la production.
2. Si les contrôles passent, il construit une archive avec les dépendances de
   production. La livraison ne contient ni `.env`, ni stockage, ni base locale,
   ni fichier privé. Le job de livraison reçoit uniquement une clé SSH dédiée.
3. Alwaysdata vérifie l’empreinte de l’archive, prend un verrou et ignore un
   workflow plus ancien qu’une version déjà livrée. La clé du serveur SSH est
   vérifiée avec `deployment/alwaysdata_known_hosts`.
4. Le site passe brièvement en maintenance. Une sauvegarde SQL, de la
   configuration et du code est conservée **sur le serveur**, dans le dossier
   privé `/home/rdsa/rdsa-private/ci`. Aucune sauvegarde n’est envoyée à GitHub.
5. Les fichiers sont mis à jour, en préservant le `.env`, `storage`, le lien
   public des fichiers importés et les dossiers de cache. Les migrations et
   caches sont appliqués, puis le contrôle de production est lancé.
6. Le site rouvre et `/health`, l’accueil et la connexion sont vérifiés. Le SHA
   et le numéro du workflow réussi sont enregistrés dans `last-success.json`.
   Les fichiers CSS et JavaScript déclarés dans le manifeste sont aussi vérifiés
   par HTTPS. Les caches PHP de bootstrap sont régénérés pour les dépendances
   de la nouvelle version.

### Secret GitHub requis

Dans [les secrets Actions du dépôt](https://github.com/D-O-Dev/RDSA_WEBSITE/settings/secrets/actions),
créer **ALWAYSDATA_SSH_KEY** avec le contenu du fichier local privé
`storage/app/rdsa/private/github-actions-deploy-ed25519` (sans `.pub`). Cette
clé est dédiée à GitHub Actions ; sa partie publique est déjà autorisée sur le
serveur avec les redirections et le terminal interactif désactivés.

Si GitHub indique `error in libcrypto`, la valeur du secret n’est pas une clé
privée OpenSSH lisible. Ouvrir le fichier dédié dans l’éditeur, sélectionner
tout son contenu, et remplacer la valeur du secret avec les lignes
`-----BEGIN OPENSSH PRIVATE KEY-----` et `-----END OPENSSH PRIVATE KEY-----`,
ainsi que toutes les lignes intermédiaires. Ne pas coller un chemin de fichier.
Le workflow vérifie ce format avant toute connexion et normalise les fins de
ligne Windows. Après correction du secret, relancer un workflow : les secrets
de dépôt d’un run déjà démarré peuvent conserver leur ancienne valeur.

Ne pas utiliser le jeton API alwaysdata, la clé APP_KEY, un mot de passe SQL ou
la clé SSH personnelle pour ce secret. Le secret est géré par GitHub et ne doit
jamais être commité. Le secret a été ajouté et le premier déploiement complet a réussi le
1er novembre 2026 : [workflow n°3](https://github.com/D-O-Dev/RDSA_WEBSITE/actions/runs/41265898231).
Le SHA livré est enregistré sur le serveur ; les sauvegardes privées ont été
vérifiées. Les prochains push sur `main` suivent automatiquement ce parcours.

### Si une livraison échoue

Un échec de tests ou de compilation ne change rien au serveur. Un échec de
sauvegarde n’écrase pas le code existant. Un échec avant les migrations restaure
le code et rouvre le site. Une maintenance manuelle déjà présente bloque le job
sans la lever.

Un échec après le début des migrations restaure le code mais **conserve la
maintenance** : il faut examiner les logs Actions et le dossier privé `ci/failed`
avant de rouvrir. La base n’est jamais restaurée automatiquement, afin de ne
pas perdre de données nouvelles. La sauvegarde privée sert à une récupération
contrôlée. `ci/previous` conserve la dernière sauvegarde précédant une livraison
réussie ; `ci/failed` la dernière livraison échouée.

Les migrations futures doivent être compatibles avec le code précédent pendant
la livraison. Le pipeline ne lance ni `migrate:fresh`, ni `key:generate`, ni import
de base locale, ni modification des comptes.

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
une présence enregistrée bloque cette suppression ordinaire. En V2, le Super ADMIN dispose d’une procédure distincte et confirmée pour les tests scannés.

Le partage WhatsApp manuel est disponible. L’envoi automatique exige un compte
fournisseur, ses identifiants, un modèle approuvé et une livraison réelle vérifiée.
Ne pas l’annoncer actif avant ce test. Si activé, planifier `schedule:run` chaque
minute dans alwaysdata. Aucun envoi automatique n’est configuré sur la base neuve.

La cloche admin vérifie les demandes et messages pendant que l’admin est ouvert ;
les annonces publiques se rafraîchissent également sur une page visible.
La V2 ajoute des notifications Web Push facultatives dans Mon compte et dans le bouton Informations et questions. Elles nécessitent l’accord du navigateur et le planificateur actif ; la réception reste dépendante du système.

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

## Bouton Continuer et ouverture des réservations

Une édition doit être **publiée, ouverte et non archivée** pour que les visiteurs
puissent réserver. Le tableau de bord signale l’absence d’édition ouverte et
renvoie vers Éditions. Sans édition ouverte, le formulaire public affiche un
message de fermeture et désactive la saisie ; il ne doit pas accepter une
réservation sans événement.

Après avoir ouvert l’édition, recharger la page publique déjà affichée pour
récupérer ses informations et un formulaire à jour. Renseigner le nom, le numéro
et le choix de première participation. En choisissant une participation antérieure,
sélectionner au moins une édition historique, puis Continuer affiche le
récapitulatif. La réservation n’est envoyée qu’après sa confirmation explicite.


## Livraison V2

Avant la mise en ligne, vérifier les nouvelles migrations, l’extension PHP ZIP,
la compilation et la suite de tests. Les migrations sont additives et n’effacent
ni les éditions ni les inscriptions existantes. Le premier administrateur actif
reçoit le rôle Super ADMIN ; aucun mot de passe n’est remplacé.

Après livraison : vérifier les nouvelles rubriques du guide, les exports et
Gérer / corriger. Ne pas supprimer un billet réel pendant la recette. Les tests
qui créent puis effacent des réservations sont réalisés sur une base QA isolée.
Une restauration de sauvegarde est une opération explicite réservée au
propriétaire : elle remplace les données métier par celles de l’archive.

Le SMTP et WhatsApp automatique demandent de vrais accès fournisseur. Les
notifications demandent une autorisation sur chaque appareil. La sauvegarde
quotidienne reste à activer selon l’espace disponible. Le planificateur doit
être vérifié après installation ; son dernier passage apparaît dans Sauvegardes.
