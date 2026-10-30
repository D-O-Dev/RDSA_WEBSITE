# RDSA — état du développement

Le projet est en développement. Aucun hébergement ni domaine n’est choisi et
aucune mise en ligne n’a été effectuée. Le design des deux interfaces doit encore
faire l’objet d’une recette détaillée avec le propriétaire du projet.

## Fonctionnement déjà raccordé

- Réservation gratuite, une personne par demande, vérification et confirmation
  des informations, limitations de fréquence et protection contre les doublons.
- Validation ou refus depuis l’administration ; émission d’un billet individuel
  après validation uniquement ; suivi privé et recherche du billet sur le site.
- QR personnel, PDF, impression et partage WhatsApp manuel. Envoi automatique
  facultatif raccordé à Meta, mais non activable réellement sans fournisseur,
  identifiants et modèle approuvé ; aucune livraison réelle n’a été testée.
- Contrôle par caméra ou référence, confirmation d’entrée, refus des doubles
  passages et registre des présences avec auteur et date.
- Éditions, contenus publics, médiathèque sociale et questions privées avec
  réponses de l’équipe.
- Calendrier réel des éditions et des rendez-vous internes, avec navigation
  mensuelle et création/modification/retrait des préparatifs privés.
- Comptes de l’équipe administrables : administrateur ou contrôleur des billets,
  désactivation, changement de mot de passe et restrictions côté serveur.

## Suite du développement

1. Reprendre les fonctions restantes du modèle `rdsa-admin.html` :
   annonces/FAQ, statistiques détaillées et journal des actions. Leur comportement
   réel doit être raccordé aux données ; aucun écran de démonstration ne doit être
   présenté comme une fonction terminée.
2. Faire une recette métier complète avec des données fictives sur téléphone :
   demande → validation → récupération PDF → scan réel → seconde entrée refusée.
   Le lecteur a été testé avec les références et les contenus QR ; l’autorisation
   et la qualité de la caméra doivent être vérifiées sur un téléphone réel.
3. Revoir chaque écran public et admin : lisibilité, couleurs, espaces, arrondis,
   liens, états vides, erreurs, écrans mobiles, ouvertures et animations. Conserver
   les choix déjà validés ; ne pas ajouter de section publique sans accord.
4. Préparer ensuite l’installation et les sauvegardes pour un hébergement gratuit
   compatible avec PHP 8.3 minimum et la base choisie, puis faire une restauration
   de test. HTTPS sera nécessaire pour la caméra sur téléphone.
5. Seulement après validation de ces étapes : configuration réelle et mise en
   ligne, contrôle du domaine, du stockage, des limites serveur et du scheduler.

## Vérifications du lot comptes et sécurité

- Mise à niveau vers Laravel 13 et Vite 8, sans changement de stack ni refonte.
- Tests des droits, de la validation, des sessions et des parcours existants.
- Audits Composer/npm des dépendances, à relancer régulièrement :
  `composer audit` et `npm audit`.
- Scénarios navigateur sur une base SQLite fictive distincte, sans réservation,
  message ou compte ajouté dans les données réelles.
