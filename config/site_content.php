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
    'identity' => ['label' => 'Logo de la RDSA', 'icon' => 'award', 'description' => 'Le logo public et le fond de l’administration, par lien image ou fichier existant.', 'fields' => [
        'admin_background_path' => ['label' => 'Fond de l’administration', 'type' => 'image', 'default' => 'images/gallery/rdsa2/rdsa2-img-h42.jpeg'],
        'ticket_logo_path' => ['label' => 'Logo intégré aux billets PDF (image locale ou téléversée)', 'type' => 'image', 'default' => 'images/logo/logo-ticket.jpg'],
        'logo_path' => ['label' => 'Logo du site', 'type' => 'image', 'default' => 'images/logo/logo.png'],
    ]],
    'publication' => ['label' => 'Rubriques et navigation', 'icon' => 'layout-text-sidebar', 'description' => 'Choisis les rubriques visibles et leur ordre sur la page publique.', 'fields' => [
        'show_rdsa' => ['label' => 'Afficher la présentation', 'type' => 'boolean', 'default' => '1'],
        'show_editions' => ['label' => 'Afficher les éditions', 'type' => 'boolean', 'default' => '1'],
        'show_medias' => ['label' => 'Afficher la galerie', 'type' => 'boolean', 'default' => '1'],
        'show_participer' => ['label' => 'Afficher les réservations', 'type' => 'boolean', 'default' => '1'],
        'show_contact' => ['label' => 'Afficher le contact', 'type' => 'boolean', 'default' => '1'],
        'section_order' => ['label' => 'Ordre des rubriques', 'type' => 'section_order', 'default' => 'rdsa,editions,medias,participer,contact'],
        'nav_rdsa' => ['label' => 'Nom du menu présentation', 'default' => 'La RDSA'],
        'nav_editions' => ['label' => 'Nom du menu éditions', 'default' => 'Éditions'],
        'nav_medias' => ['label' => 'Nom du menu galerie', 'default' => 'Médias'],
        'nav_participer' => ['label' => 'Nom du menu réservation', 'default' => 'Participer'],
        'nav_contact' => ['label' => 'Nom du menu contact', 'default' => 'Contact'],
    ]],
    'general_contact' => ['label' => 'Coordonnées permanentes', 'icon' => 'telephone', 'description' => 'Ces coordonnées restent disponibles lorsqu’aucune édition n’est ouverte.', 'fields' => [
        'general_phone' => ['label' => 'Téléphone permanent', 'type' => 'phone', 'optional' => true, 'default' => ''],
        'general_whatsapp' => ['label' => 'WhatsApp permanent', 'type' => 'phone', 'optional' => true, 'default' => ''],
        'general_venue' => ['label' => 'Adresse permanente', 'optional' => true, 'default' => ''],
        'general_map_url' => ['label' => 'Lien Google Maps permanent', 'type' => 'map', 'optional' => true, 'default' => ''],
    ]],
    'search' => ['label' => 'Présentation dans les recherches', 'icon' => 'search', 'description' => 'Le titre et la description publics utilisés pour présenter le site.', 'fields' => [
        'seo_title' => ['label' => 'Titre public du site', 'default' => 'RDSA - Réunion des Assoiffés de Prière & d’Adoration'],
        'seo_description' => ['label' => 'Description du site', 'type' => 'textarea', 'default' => "RDSA - Réunion des Assoiffés de Prière & d'Adoration"],
        'site_author' => ['label' => 'Auteur / créateur du site', 'optional' => true, 'default' => 'Jada Firmament'],
    ]],

];
