<!DOCTYPE html>
<html lang="fr">

<head>
    
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="description" 
        content="RDSA - Réunion des Assoiffés de Prière & d'Adoration">
    
    <title>@yield('title', 'RDSA')</title>
    
    @if($designPreview ?? false)<script>
    document.addEventListener('submit', function(e){e.preventDefault();e.stopImmediatePropagation()}, true);
    document.addEventListener('click', function(e){var link=e.target.closest('a[href]');if(link && !link.getAttribute('href').startsWith('#')){e.preventDefault();e.stopImmediatePropagation()}}, true);
    </script>@endif
    @vite(['resources/css/components.css', 'resources/css/app.css', 'resources/js/app.js'])
</head>


<body @if($designPreview ?? false) data-design-preview="true" @endif data-site-design="{{ $siteDesign['preset'] }}" data-design-active="{{ $siteDesign['active'] ? 'true' : 'false' }}" data-design-effect="{{ $siteDesign['effect'] }}" data-design-radius="{{ $siteDesign['radius'] }}" data-design-motion="{{ $siteDesign['motion'] }}" data-design-font="{{ $siteDesign['font'] }}" style="{{ $siteDesign['style'] }}">

    @include('partials.entry-loader')
    @yield('content')


</body>


</html>