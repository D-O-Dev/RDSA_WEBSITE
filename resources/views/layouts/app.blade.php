<!DOCTYPE html>
<html lang="fr">

<head>
    
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo/logo.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#080d1a">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="description" 
        content="{{ $siteContent['seo_description'] ?? \App\Models\SiteContent::current()['seo_description'] }}">
    
    <title>{{ $siteContent['seo_title'] ?? \App\Models\SiteContent::current()['seo_title'] }}</title>
    <meta name="author" content="{{ $siteContent['site_author'] ?? \App\Models\SiteContent::current()['site_author'] }}">
    <link rel="canonical" href="{{ route('home') }}">
    
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
