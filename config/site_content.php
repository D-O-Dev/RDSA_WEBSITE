<?php

return [
    'hero' => ['label' => 'Bannière d’accueil', 'icon' => 'house', 'description' => 'Les deux lignes qui présentent la RDSA en haut du site.', 'fields' => [
        'hero_subtitle' => ['label' => 'Nom de la rencontre', 'default' => 'RÉUNION DES ASSOIFFÉS'],
        'hero_subtitle_gold' => ['label' => 'Complément du nom', 'default' => 'DE PRIÈRE & D’ADORATION'],
    ]],
    'about' => ['label' => 'Présentation de la RDSA', 'icon' => 'info-circle', 'description' => 'Le titre et le texte qui expliquent ce qu’est la RDSA.', 'fields' => [
        'about_kicker' => ['label' => 'Petit texte au-dessus du titre', 'default' => 'À PROPOS'],
        'about_heading' => ['label' => 'Titre de la présentation', 'default' => 'LA RDSA'],
        'about_description' => ['label' => 'Texte de présentation', 'type' => 'textarea', 'default' => 'Une rencontre de prière, d’adoration et d’enseignement pour rechercher la présence de Dieu et manifester Sa gloire.'],
        'about_button' => ['label' => 'Texte du bouton de la présentation', 'default' => 'EN SAVOIR PLUS'],
    ]],
    'values' => ['label' => 'Les quatre valeurs de la RDSA', 'icon' => 'heart', 'description' => 'Les quatre blocs affichés à côté de la présentation.', 'fields' => [
        'value_1_title' => ['label' => 'Titre', 'default' => 'UN SEUL BUT'],
        'value_1_text' => ['label' => 'Message', 'type' => 'textarea', 'default' => "RECHERCHER\nLA PRÉSENCE\nDE DIEU"],
        'value_2_title' => ['label' => 'Titre', 'default' => 'ADORATION'],
        'value_2_text' => ['label' => 'Message', 'type' => 'textarea', 'default' => "DES MOMENTS\nPUISSANTS DE\nLOUANGE"],
        'value_3_title' => ['label' => 'Titre', 'default' => 'ENSEIGNEMENTS'],
        'value_3_text' => ['label' => 'Message', 'type' => 'textarea', 'default' => "DES PAROLES\nQUI TRANSFORMENT\nLES VIES"],
        'value_4_title' => ['label' => 'Titre', 'default' => 'IMPACT'],
        'value_4_text' => ['label' => 'Message', 'type' => 'textarea', 'default' => "DES VIES\nCHANGÉES POUR\nSA GLOIRE"],
    ]],
    'editions' => ['label' => 'Nos éditions', 'icon' => 'calendar3', 'description' => 'Le titre placé au-dessus des anciennes éditions.', 'fields' => [
        'editions_kicker' => ['label' => 'Petit texte au-dessus du titre', 'default' => 'NOTRE HISTOIRE'],
        'editions_heading' => ['label' => 'Titre de la rubrique', 'default' => 'NOS ÉDITIONS'],
    ]],
    'gallery' => ['label' => 'Galerie photos et vidéos', 'icon' => 'images', 'description' => 'Les textes qui invitent les visiteurs à ouvrir la galerie.', 'fields' => [
        'media_kicker' => ['label' => 'Petit texte au-dessus du titre', 'default' => 'REVIVEZ NOS MOMENTS'],
        'media_heading' => ['label' => 'Titre de la galerie', 'default' => 'GALERIE PHOTOS'],
        'media_album_label' => ['label' => 'Nom de l’album', 'default' => 'L’ALBUM RDSA'],
        'media_invitation' => ['label' => 'Message d’invitation à ouvrir l’album', 'type' => 'textarea', 'default' => "Nos souvenirs,\nà vivre en grand."],
    ]],
    'contact' => ['label' => 'Nous contacter', 'icon' => 'chat-dots', 'description' => 'Le message affiché à côté des coordonnées et de la carte.', 'fields' => [
        'contact_kicker' => ['label' => 'Petit texte au-dessus du titre', 'default' => 'RESTONS EN CONTACT'],
        'contact_heading' => ['label' => 'Titre de la rubrique', 'default' => 'NOUS CONTACTER'],
        'contact_description' => ['label' => 'Message d’accueil', 'type' => 'textarea', 'default' => 'Une question concernant la RDSA, la participation ou le déroulement de l’événement ? Notre équipe est disponible pour t’accompagner.'],
    ]],
    'footer' => ['label' => 'Bas de page', 'icon' => 'layout-text-window-reverse', 'description' => 'La présentation courte et l’invitation à participer, tout en bas du site.', 'fields' => [
        'footer_description' => ['label' => 'Texte affiché sous le logo', 'type' => 'textarea', 'default' => 'Réunion des Assoiffés de Prière & d’Adoration. Une génération qui a soif de Dieu.'],
        'footer_title' => ['label' => 'Titre de l’invitation à participer', 'default' => 'PRÊT À NOUS REJOINDRE ?'],
        'footer_invitation' => ['label' => 'Message d’invitation', 'type' => 'textarea', 'default' => 'Réserve ta participation et prépare-toi à vivre un moment particulier dans la présence de Dieu.'],
        'footer_button' => ['label' => 'Texte du bouton de participation', 'default' => 'JE PARTICIPE'],
    ]],
    'identity' => ['label' => 'Logo de la RDSA', 'icon' => 'award', 'description' => 'Le logo affiché dans le menu et en bas du site public.', 'fields' => [
        'logo_path' => ['label' => 'Logo actuel', 'type' => 'image', 'default' => 'images/logo/logo.png'],
    ]],
];
