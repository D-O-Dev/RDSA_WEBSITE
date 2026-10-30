# RDSA — réservation et contrôle des billets

Suivi de conception et étapes restantes : [État du développement](docs/ETAT_DU_PROJET.md).

Application Laravel 13 / Blade, JavaScript, Bootstrap et Vite. La participation
se fait désormais par réservation gratuite : aucun prix, transfert Wave,
justificatif ou validation financière ne fait partie du parcours.

## Parcours public

1. **Informations** : nom, téléphone, participation aux éditions précédentes et
   choix de réception WhatsApp, avec la présentation et le bouton CONTINUER d’origine.
2. **Vérification** : récapitulatif, bouton MODIFIER et confirmation explicite
   des informations. Le bouton CONFIRMER transmet la demande.
3. **Confirmation** : demande reçue, référence privée de suivi et état en attente.
   **Aucun billet, QR ou PDF n’est émis à ce stade.**
4. Dans **Admin → Réservations**, l’équipe vérifie les informations puis valide
   la demande ou la refuse avec un motif. L’auteur et la date sont enregistrés.
5. Une validation émet le billet individuel dans la même transaction. Une erreur
   d’émission annule la validation ; la demande reste en attente. Les doubles
   validations sont refusées. La page de suivi mène au billet après validation.
6. À l’entrée, un administrateur inspecte le QR avec la caméra ou saisit la
   référence, puis confirme le passage. Une seconde entrée est refusée.

« Mon billet » retrouve aussi une demande en attente avec sa référence et le
numéro de téléphone correspondant. Le suivi est privé et ne contient jamais de
faux QR de billet. Les demandes en attente ou refusées ne peuvent pas entrer.

Le téléphone seul ne permet pas de consulter un billet. Les liens privés et les
codes ne doivent pas être partagés. Les pages et réponses privées utilisent
`no-store`. Les jetons d’accès et de contrôle sont distincts, chiffrés en base ;
leurs empreintes permettent les vérifications. Les recherches de billets sont
limitées en fréquence. La confirmation historique d’une réservation nécessite
la session qui l’a créée ; une référence seule ne divulgue pas le billet ou la demande.

Les jetons de formulaire sont liés à la session, expirent après 30 minutes et
plusieurs onglets restent utilisables. Le formulaire comporte un champ piège
invisible et un délai minimum serveur de 3 secondes. Le même envoi répété
restitue la même demande. Un téléphone normalisé ne peut créer qu’une demande
par édition, même sous un autre nom ; un index unique en base renforce cette
protection. Les envois sont limités à 3/minute, 5/heure et 20/jour par IP, ainsi
qu’à 5/heure par session, indépendamment des limites de consultation des billets.
Ces mesures réduisent les soumissions abusives ; elles ne prouvent pas à elles
seules l’identité ou la possession du téléphone. La validation humaine reste
obligatoire avant émission d’un billet. Aucun fournisseur SMS/OTP n’est ajouté.

La vérification du billet et la confirmation de passage utilisent des verrous
et une contrainte unique sur la présence pour empêcher la double validation.

## Administration

`/admin/login` — accès réservé aux administrateurs et aux contrôleurs actifs.

- **Tableau de bord** : réservations, demandes à vérifier, confirmations, billets et entrées réels,
  filtrables par édition.
- **Réservations** : recherche automatique, vérification des demandes, validation,
  refus motivé et consultation du billet associé.
- **Billets** : lien privé, PDF, partage WhatsApp manuel et suivi des envois.
  Les billets utilisés restent consultables et téléchargeables sans QR actif ;
  le renvoi WhatsApp reste réservé aux billets valides.
- **Contrôle des billets / Présences** : caméra, référence, inspection et journal.
- **Éditions** : nom, thème, description, date, lieu, localisation, unique image de
  fond du hero, publication, ouverture et archivage. Une seule édition est ouverte.
- **Médiathèque** : médias, albums, ordre, formats et publication.
- **Messages** : questions privées, réponses, archivage et compteur des nouveautés.
- **WhatsApp** : configuration facultative de l’envoi automatique.

Les routes, contrôleurs, services, modèles et écrans opérationnels de paiement
et de gestion Wave ont été retirés. Les anciennes tables et colonnes financières
restent archivées en base pour ne pas détruire les enregistrements historiques ;
elles ne sont ni affichées ni utilisées pour autoriser une réservation, émettre
un billet après validation, produire un PDF, envoyer WhatsApp ou contrôler une entrée. Les
réservations nouvelles utilisent zéro pour les anciennes colonnes obligatoires.
Les anciens billets confirmés restent utilisables. Les anciennes réservations
non confirmées ne deviennent pas automatiquement confirmées.

## Identité et affichage

Le fichier `resources/css/app.css` est identique à la version d’origine de Git :
composition, couleurs, tailles, arrondis, ombres et transitions de la page initiale.
Les surcharges Cinzel et verre ajoutées pendant les retouches ont été retirées.
Le parallaxe du hero reprend le code original (vitesse 0,20, rendu par
`requestAnimationFrame`, préférence de réduction des animations respectée).
Le menu mobile utilise la transition Collapse de Bootstrap déjà présent dans le
projet. L’administration reprend les styles et le mouvement du fond de la maquette
`rdsa-admin.html`, avec respect de la préférence de réduction des animations.

Les styles nécessaires aux fonctionnalités ajoutées sont isolés dans
`components.css` et chargés avant la feuille d’origine. Ils suivent les boutons
et arrondis des composants initiaux ; leurs transitions utilisent 350 ms,
et le changement d’étape conserve le fondu d’origine de 400 ms. La galerie en
une box, les dialogues, la carte réelle, les recherches et la vérification des
demandes restent disponibles. La police Inter est hébergée localement.

Le billet et le PDF comportent le logo fourni et un QR bleu arrondi, généré
localement par BaconQrCode. Dompdf produit un vrai PDF autonome. La version JPEG
`public/images/logo/logo-ticket.jpg` conserve le logo visible même sans PHP GD.

## Google Maps

Dans **Éditions**, fournir le lien du lieu Google Maps ou son URL d’intégration
(sans balise iframe). Les coordonnées latitude/longitude peuvent aussi être
renseignées et sont prioritaires. Les liens contenant les coordonnées du repère,
les identifiants Google de lieu/CID et les URL d’intégration sont reconnus. Les
liens courts sont résolus à l’enregistrement avec des domaines autorisés et des
limites de redirection et de durée. La position de caméra `@lat,lng` ne remplace
pas le repère du lieu.

La vraie carte et son aperçu dans l’administration utilisent une iframe Google
Maps ; l’itinéraire correspond au même lieu. Sans lien précis ni coordonnées,
une carte Google Maps recherche le nom et l’adresse renseignés, avec une mention
qui le précise ; aucun point GPS n’est inventé. Les champs Téléphone de contact
et WhatsApp de contact dans Éditions alimentent les cartes de la section
« Nous contacter » ; aucune URL fictive n’est utilisée si ces champs sont vides. Aucun faux repère n’est inventé
lorsqu’aucune localisation n’a été fournie. Un nouveau lien non intégrable est
refusé avec une indication pour renseigner une URL d’intégration ou les
coordonnées. La carte nécessite une connexion à Google Maps dans le navigateur.

## Galerie et messages

Une seule carte ouvre la galerie. Le grand lecteur comporte filtres d’album et
de type, miniatures défilantes, flèches et navigation tactile. Les images, les
couvertures, les miniatures et les vidéos importées gardent leur format sans
recadrage ni recompression. Le lecteur est arrêté à la fermeture.

Formats : JPG/PNG/WebP, MP4/WebM ; liens YouTube, Facebook, TikTok, Instagram et
Vimeo. Les liens courts TikTok et fb.watch sont résolus si le fournisseur le
permet. Le format vertical/horizontal/carré des lecteurs sociaux peut être
précisé dans l’éditeur. Le champ de lien vidéo est directement visible dans le formulaire de
médiathèque, y compris lorsque le type initial est Photo. Coller un lien
sélectionne automatiquement Vidéo ; le serveur applique aussi ce choix.
Une croix en haut du formulaire permet de le fermer sans descendre jusqu’au bouton Annuler.
Les boutons d’ouverture des médias ajoutés par erreur ont été retirés.
Le lien d’origine reste disponible lorsque la plateforme
limite l’intégration (vidéo privée, suppression, connexion ou intégration désactivée).

Les fichiers existants de `public/images` sont sélectionnables. Les imports vont
dans `storage/app/public/rdsa/media`. Limites : 10 Mo/photo, 50 Mo/vidéo, dans la
limite PHP du serveur affichée par l’éditeur. Exécuter une fois :

```sh
php artisan storage:link
php artisan db:seed --class=MediaLibrarySeeder
```

Le seeder conserve les modifications administrateur. Seuls les médias publiés,
liés à une édition publiée lorsqu’ils ont une édition, sont publics.

Le bouton flottant ouvre le formulaire de questions. Le visiteur reçoit une
conversation privée sans compte et peut consulter les réponses ou compléter sa
question. Les nouveaux échanges sont actualisés sans effacer le brouillon.
CSRF, jeton de soumission, anti-robot et limitation de fréquence protègent l’envoi.
Les réponses des administrateurs apparaissent dans la conversation ; elles ne
sont pas envoyées automatiquement par e-mail ou WhatsApp.

## WhatsApp facultatif

L’envoi automatique se déclenche **après validation administrative**, uniquement si la
configuration est activée et si le visiteur l’a demandé. Il nécessite un compte
Meta Cloud API, ses identifiants et un modèle approuvé avec en-tête document.
Les cinq variables du corps sont : nom, édition, référence billet, lien privé,
code privé. Le texte du modèle doit parler de réservation, pas de paiement.
Le PDF est transmis comme document. Ne pas activer sans configuration valide.

L’outbox est enregistrée avec le billet ; l’envoi intervient après validation
de la transaction. Un échec de WhatsApp n’annule pas la réservation. Un résultat
incertain n’est pas renvoyé à l’aveugle. Les webhooks signés distinguent
acceptation, livraison et lecture. Le partage manuel reste disponible.

Configurer le scheduler Laravel en production pour la récupération des envois
et tâches planifiées :

```sh
* * * * * cd /chemin/RDSA_WEBSITE && php artisan schedule:run >> /dev/null 2>&1
```

## Installation et vérifications

Configurer `.env`, la base et l’URL, puis :

```sh
composer install
npm ci
php artisan migrate --force
php artisan storage:link
npm run build
php artisan rdsa:create-admin
```

La commande du premier administrateur refuse de créer un autre compte si un
administrateur existe déjà. Ne pas versionner les identifiants. Sauvegarder la
base, `storage/app`, les images et `APP_KEY` (nécessaire au déchiffrement des jetons).
Les preuves historiques restent privées et ne sont pas publiées dans le stockage
public. Configurer HTTPS et `APP_DEBUG=false` en production.

```sh
php artisan test --compact
npm run build
git diff --check
```

Les tests utilisent SQLite en mémoire et ne modifient pas MySQL. Les contrôles
navigateur locaux utilisent une base SQLite fictive distincte ; aucun paiement,
message WhatsApp ou réservation réel n’est envoyé pendant ces contrôles.

### Interactions et modèle admin

Le fichier `../rdsa-admin.html` reste la référence visuelle. Son menu rétractable
(préférence locale), sa recherche rapide et ses raccourcis sont rétablis sur les
routes Laravel : Ctrl/Cmd + K ouvre la palette, les flèches sélectionnent une
page/action et Entrée l’exécute. La recherche ignore les accents. Elle porte sur
les pages et actions existantes ; les recherches de participants et de billets
restent dans leurs pages respectives.

Les éditeurs d’éditions et de médias se ferment par leur croix ou Échap ; le
focus reste dans la fenêtre et le fond ne défile pas. Les cartes apparaissent à
l’entrée dans l’écran, les boutons répondent à l’activation et les soumissions
natives montrent un indicateur d’attente en limitant les doubles clics. Les
formulaires AJAX conservent leur propre validation et leurs retours d’erreur.
Les recherches instantanées animent les nouvelles cartes sans faire clignoter
la page entière. L’option système de réduction des animations est respectée.
Les mêmes effets de boutons sont appliqués au billet à l’écran ; le PDF ne
charge aucun script d’animation.

Le modèle contient aussi des démonstrations d’annonces/FAQ et audit.
Ces modules restent à concevoir et ne sont pas simulés dans l’administration réelle.
La gestion réelle des comptes et les accès administrateur/contrôleur sont décrits ci-dessous. Les anciennes
fonctions de paiement du modèle restent retirées.

### Contenu du site public

`/admin/content` permet aux administrateurs de modifier les deux lignes
présentant la RDSA dans la bannière et le logo,
la présentation et ses quatre axes, les titres des éditions et de la galerie,
l’introduction du contact et les invitations du pied de page. Les valeurs sont
enregistrées ensemble en base dans `site_contents` ; les textes d’origine servent
de valeurs initiales sans changer le rendu. Les dates, le thème, le fond du hero,
le lieu, la carte et les numéros de contact restent configurés dans l’édition.

Les images sont choisies parmi les fichiers JPG/PNG/WebP de `public/images`, avec
aperçu immédiat ; aucun chemin externe ou sortant de ce dossier n’est accepté.
Les textes sont échappés et les sauts de ligne des quatre axes et de l’invitation
à la galerie sont conservés. La validation d’un champ invalide empêche tout
enregistrement partiel. Aucun formulaire de paiement ni section publique
supplémentaire n’est ajouté.

Pour une autre installation : exécuter `php artisan migrate` pour créer la table
avant de servir la nouvelle version, puis `npm run build`.


L’éditeur de contenu est organisé en huit rubriques repliables portant les noms
visibles sur le site. Chaque titre se saisit dans un seul champ. Les quatre
valeurs ont chacune leur titre et leur message, et le logo se choisit par des
vignettes sans afficher les chemins des fichiers. Un bouton d’enregistrement est
disponible en haut et en bas. Une erreur de validation ouvre automatiquement la
rubrique concernée. Les anciens titres enregistrés en deux parties sont repris
sans perte de texte.

La bannière conserve une seule image de fond, configurée dans Éditions, et son
compteur. Les deux photos superposées et leurs champs de configuration sont
retirés. Les fichiers de la médiathèque restent disponibles.


### Équipe et sécurité des comptes

Dans **Comptes et accès**, un administrateur peut créer un membre, modifier ses
informations, changer son rôle, réinitialiser son mot de passe et désactiver son
accès. Le rôle **Contrôleur des billets** donne uniquement accès au scan, à la
confirmation des entrées, aux présences et à son propre compte. Ces limites sont
appliquées aux routes côté serveur et aux menus ; les contenus, les réservations,
les PDF privés et les comptes de l’équipe restent réservés aux administrateurs.
Les comptes existants restent actifs avec leur accès actuel.

Toute modification d’un compte d’équipe requiert le mot de passe de
l’administrateur. Il est impossible de retirer son propre accès administrateur
ou de supprimer le dernier administrateur actif. La désactivation prend effet
aux requêtes suivantes. Un changement de mot de passe invalide les anciennes
sessions, y compris celles ouvertes sur un autre appareil. **Mon compte** permet
de changer son propre mot de passe sans passer par le code ni la base.

Les nouveaux mots de passe doivent contenir au moins 12 caractères avec
majuscules, minuscules, chiffres et symboles. Les messages de validation sont en
français. Aucun mot de passe n’est prérempli dans les formulaires.

Le socle utilise désormais PHP **8.3 minimum**, Laravel **13**, PHPUnit **12** et
Vite **8** avec son plugin Laravel. Node doit satisfaire `^20.19.0 || >=22.12.0`.
La structure Blade/JavaScript/Bootstrap et le noyau Laravel existants sont
conservés. Les guides officiels des migrations
[11](https://laravel.com/framework/docs/11.x/upgrade),
[12](https://laravel.com/framework/docs/12.x/upgrade) et
[13](https://laravel.com/framework/docs/13.x/upgrade) ont servi de référence.
Après récupération du code : `composer install`, `npm ci`, `php artisan migrate`,
puis `npm run build`.

Les pages privées sont servies avec `no-store` et `noindex`, sans transmettre
leur URL privée dans le référent. Les en-têtes autorisent la caméra du site pour
le contrôle des billets, et interdisent le micro et la géolocalisation. Les hôtes
sont contrôlés en production et les proxys de confiance peuvent être renseignés
par `TRUSTED_PROXIES` ; aucune confiance globale des proxys n’est activée.
`/health` vérifie la connexion à la base et renvoie seulement un état public,
sans configuration ni détails d’erreur.

Le projet reste en développement : la recette visuelle détaillée des deux
interfaces et le choix de l’hébergement gratuit viendront avant toute mise en
ligne. Aucun hébergement n’est configuré automatiquement par ces changements.


### Calendrier de l’administration

Dans **Calendrier**, les éditions sont affichées directement à leur date réelle,
y compris les brouillons et les éditions archivées. Modifier la date ou l’état
d’une édition met donc à jour son affichage sans dupliquer de données. Le lien
Modifier d’une édition ouvre son formulaire habituel.

Les administrateurs peuvent aussi ajouter des rendez-vous internes : nom, date
et heure, lieu et notes facultatifs. Ils ne sont jamais affichés sur le site
public et ne créent aucune réservation. Le clic sur un jour préremplit la date ;
le choix du mois et les flèches naviguent automatiquement lorsque JavaScript est
actif. Sans JavaScript, les formulaires et les liens restent utilisables.

Les rendez-vous peuvent être déplacés et retirés après confirmation. Le retrait
utilise une suppression logique et préserve l’auteur du rendez-vous ; aucune
édition ni réservation n’est modifiée. Le rôle Contrôleur des billets n’a accès
ni aux écrans ni aux opérations du calendrier. Sur mobile, les compteurs des
jours donnent accès au programme détaillé affiché sous la grille.
