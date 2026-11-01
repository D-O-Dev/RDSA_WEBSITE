<meta name="rdsa-admin-background" content="{{ asset(\App\Models\SiteContent::current()['admin_background_path']) }}">
<meta name="rdsa-admin-logo" content="{{ asset(\App\Models\SiteContent::current()['logo_path']) }}">
<title>RDSA · Administration</title>
<nav id="nav">@include('admin.partials.navigation')</nav>
@include('admin.partials.profile')
<div id="crumb">Administration</div>
<h1 id="title">@yield('admin-title', 'Éditions')</h1>
<div id="views">@yield('admin-content')</div>
<div id="admin-modal-root">@yield('admin-modal')</div>
