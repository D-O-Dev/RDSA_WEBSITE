@if(request()->header('X-RDSA-Navigation') === '1')
@include('admin.partials.navigation-response')
@else
@include('admin.head')
<body>
  @include('partials.entry-loader', ['entryLabel' => 'Préparation de votre espace d’administration'])
  <!-- Parallaxe : remplace --bgimg par une de tes images publiques -->
  <div class="bg" aria-hidden="true">
    <div class="bg-img" id="bgimg"></div>
  </div>
  <div class="scrim" id="scrim"></div>

  <aside class="side" id="side">
    <div class="brand">
      <img src="{{ asset(\App\Models\SiteContent::current()['logo_path']) }}" data-admin-logo alt="RDSA"
        onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
      <div class="brand-fb">R</div>
      <div><b>RDSA</b><small>Espace d'administration</small></div>
    </div>
    <nav class="nav" id="nav">@include('admin.partials.navigation')</nav>
    @include('admin.partials.profile')
  </aside>

  <div class="main">
    <header class="top">
      <button class="ib burger" id="burger" aria-label="Ouvrir le menu"><i class="bi bi-list"></i></button>
      <div>
        <div class="crumb" id="crumb">Administration</div>
        <h1 id="title">@yield('admin-title', 'Éditions')</h1>
      </div>
      <button class="srchbtn" id="kbtn" type="button" aria-label="Rechercher une page ou une action" aria-haspopup="dialog"><i class="bi bi-search"></i><span>Rechercher…</span><kbd>Ctrl K</kbd></button>
      <div class="sp"></div>
      @can('manage-team')<button class="ib notification-bell" type="button" id="admin-notification-button" data-url="{{ route('admin.notifications.index') }}" data-user="{{ auth()->id() }}" aria-label="Notifications" aria-haspopup="dialog"><i class="bi bi-bell"></i><span data-notification-count hidden></span></button>@endcan
      <button class="ib coll" id="collapse" type="button" aria-label="Réduire le menu" aria-expanded="true" aria-controls="side"><i class="bi bi-layout-sidebar-inset"></i></button>
      <button class="ib" id="theme" aria-label="Changer de thème"><i class="bi bi-moon-stars"></i></button>
      <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="ib" type="submit" aria-label="Se déconnecter"><i class="bi bi-box-arrow-right"></i></button></form>
    </header>
    <div class="wrap" id="views">@yield('admin-content')</div>
  </div>

  @include('admin.partials.guide')
  <div id="admin-modal-root">@yield('admin-modal')</div>
  <dialog class="admin-commands admin-leave-confirm" id="admin-leave-confirm" aria-labelledby="leave-confirm-title"><h2 id="leave-confirm-title">Modifications non enregistrées</h2><p>Tu as modifié un formulaire. Veux-tu rester pour l’enregistrer ou quitter sans conserver ces modifications ?</p><div class="act"><button class="btn" type="button" data-leave="stay">Rester</button><button class="btn" type="button" data-leave="discard">Quitter sans enregistrer</button></div></dialog>
  <dialog class="admin-commands" id="admin-commands" aria-labelledby="command-title">
    <div class="command-heading"><h2 id="command-title">Accès rapide</h2><button class="ib" type="button" data-dialog-close aria-label="Fermer la recherche"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
    <label class="command-label" for="command-search">Rechercher une page ou une action</label>
    <input class="in" id="command-search" type="search" placeholder="Réservations, billets, médiathèque…" autocomplete="off" aria-controls="command-results">
    <div class="cmdl" id="command-results"></div>
    <p class="hint command-help">↑ ↓ pour naviguer · Entrée pour ouvrir · Échap pour fermer</p>
    <div class="command-status" role="status" aria-live="polite"></div>
  </dialog>
<script>
const side = document.getElementById('side'), scrim = document.getElementById('scrim');
document.getElementById('burger').onclick = () => { side.classList.add('open'); scrim.classList.add('on'); };
scrim.onclick = () => { side.classList.remove('open'); scrim.classList.remove('on'); };
document.getElementById('theme').onclick = () => {
 const root = document.documentElement;
 root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
 try { localStorage.setItem('rdsa-admin-theme', root.dataset.theme); } catch (e) {}
};
</script>
@vite('resources/js/admin.js')
@stack('scripts')
@can('manage-team')
<dialog class="admin-commands" id="admin-notifications"><div class="command-heading"><h2>Notifications</h2><button class="btn sm" type="button" data-notification-close aria-label="Fermer">×</button></div><p data-notification-summary class="hint">Chargement des alertes…</p><div data-notification-items></div><div class="act"><a class="btn" href="{{ route('admin.participations.index', ['status' => 'pending']) }}">Demandes à vérifier</a><a class="btn" href="{{ route('admin.messages.index', ['status' => 'new']) }}">Messages non lus</a></div></dialog>
@endcan
</body></html>


@endif
