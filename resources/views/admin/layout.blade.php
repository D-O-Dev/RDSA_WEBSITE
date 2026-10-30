@include('admin.head')
<body>
  <!-- Parallaxe : remplace --bgimg par une de tes images publiques -->
  <div class="bg" aria-hidden="true">
    <div class="bg-img" id="bgimg" style="--bgimg:url('/images/gallery/rdsa2/rdsa2-img-h42.jpeg')"></div>
  </div>
  <div class="scrim" id="scrim"></div>

  <aside class="side" id="side">
    <div class="brand">
      <img src="/images/logo/logo.png" alt="RDSA"
        onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
      <div class="brand-fb">R</div>
      <div><b>RDSA</b><small>Espace d'administration</small></div>
    </div>
    <nav class="nav" id="nav">
      @can('manage-team')
      <h4>Pilotage</h4>
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}"><i class="bi bi-speedometer2"></i>Tableau de bord</a>
      <a href="{{ route('admin.calendar.index') }}" class="{{ request()->routeIs('admin.calendar.*') ? 'on' : '' }}"><i class="bi bi-calendar3"></i>Calendrier</a>
      <h4>Opérations</h4>
      <a href="{{ route('admin.participations.index') }}" class="{{ request()->routeIs('admin.participations.*') ? 'on' : '' }}"><i class="bi bi-people"></i>Réservations</a>
      <a href="{{ route('admin.tickets.index') }}" class="{{ request()->routeIs('admin.tickets.*') ? 'on' : '' }}"><i class="bi bi-ticket-perforated"></i>Billets</a>
      <a href="{{ route('admin.whatsapp.edit') }}" class="{{ request()->routeIs('admin.whatsapp.*') ? 'on' : '' }}"><i class="bi bi-whatsapp"></i>WhatsApp</a>
      @endcan
      <a href="{{ route('admin.check-in.index') }}" class="{{ request()->routeIs('admin.check-in.*') ? 'on' : '' }}"><i class="bi bi-qr-code-scan"></i>Contrôle des billets</a>
      <a href="{{ route('admin.attendances.index') }}" class="{{ request()->routeIs('admin.attendances.*') ? 'on' : '' }}"><i class="bi bi-door-open"></i>Présences</a>
      @can('manage-team')
      <a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*') ? 'on' : '' }}"><i class="bi bi-chat-dots"></i>Messages <span class="cnt" data-message-count data-url="{{ route('admin.messages.count') }}" @if(!$unreadMessageCount) hidden @endif>{{ $unreadMessageCount }}</span></a>
      <h4>Site public</h4>
      <a href="{{ route('admin.content.edit') }}" class="{{ request()->routeIs('admin.content.*') ? 'on' : '' }}"><i class="bi bi-pencil-square"></i>Contenu du site</a>
      <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'on' : '' }}"><i class="bi bi-images"></i>Médiathèque</a>
      <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'on' : '' }}"><i class="bi bi-calendar-event"></i>Éditions</a>
      <h4>Équipe</h4>
      <a href="{{ route('admin.team.index') }}" class="{{ request()->routeIs('admin.team.*') ? 'on' : '' }}"><i class="bi bi-people-fill"></i>Comptes et accès</a>
      @endcan
      <h4>Mon accès</h4>
      <a href="{{ route('admin.account.edit') }}" class="{{ request()->routeIs('admin.account.*') ? 'on' : '' }}"><i class="bi bi-shield-lock"></i>Mon compte</a>
    </nav>
    <div class="me">
      <div class="av">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</div>
      <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->is_admin ? 'Administrateur' : 'Contrôleur des billets' }}</small></div>
    </div>
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
      <button class="ib coll" id="collapse" type="button" aria-label="Réduire le menu" aria-expanded="true" aria-controls="side"><i class="bi bi-layout-sidebar-inset"></i></button>
      <button class="ib" id="theme" aria-label="Changer de thème"><i class="bi bi-moon-stars"></i></button>
      <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="ib" type="submit" aria-label="Se déconnecter"><i class="bi bi-box-arrow-right"></i></button></form>
    </header>
    <div class="wrap" id="views">@yield('admin-content')</div>
  </div>

  @yield('admin-modal')
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
</body></html>
