
      @can('manage-team')
      <h4>Pilotage</h4>
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}"><i class="bi bi-speedometer2"></i><span class="nav-label">Tableau de bord</span></a>
      <a href="{{ route('admin.calendar.index') }}" class="{{ request()->routeIs('admin.calendar.*') ? 'on' : '' }}"><i class="bi bi-calendar3"></i><span class="nav-label">Calendrier</span></a>
      <a href="{{ route('admin.statistics.index') }}" class="{{ request()->routeIs('admin.statistics.*') ? 'on' : '' }}"><i class="bi bi-bar-chart-line"></i><span class="nav-label">Statistiques</span></a>
      <h4>Opérations</h4>
      <a href="{{ route('admin.participations.index') }}" class="{{ request()->routeIs('admin.participations.*') ? 'on' : '' }}"><i class="bi bi-people"></i><span class="nav-label">Réservations</span></a>
      <a href="{{ route('admin.tickets.index') }}" class="{{ request()->routeIs('admin.tickets.*') ? 'on' : '' }}"><i class="bi bi-ticket-perforated"></i><span class="nav-label">Billets</span></a>
      <a href="{{ route('admin.whatsapp.edit') }}" class="{{ request()->routeIs('admin.whatsapp.*') ? 'on' : '' }}"><i class="bi bi-whatsapp"></i><span class="nav-label">WhatsApp</span></a>
      @endcan
      <a href="{{ route('admin.check-in.index') }}" class="{{ request()->routeIs('admin.check-in.*') ? 'on' : '' }}"><i class="bi bi-qr-code-scan"></i><span class="nav-label">Contrôle des billets</span></a>
      <a href="{{ route('admin.attendances.index') }}" class="{{ request()->routeIs('admin.attendances.*') ? 'on' : '' }}"><i class="bi bi-door-open"></i><span class="nav-label">Présences</span></a>
      @can('manage-team')
      <a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*') ? 'on' : '' }}"><i class="bi bi-chat-dots"></i><span class="nav-label">Messages</span><span class="cnt" data-message-count data-url="{{ route('admin.messages.count') }}" @if(!$unreadMessageCount) hidden @endif>{{ $unreadMessageCount }}</span></a>
      <h4>Site public</h4>
      <a href="{{ route('admin.appearance.index') }}" class="{{ request()->routeIs('admin.appearance.*') ? 'on' : '' }}"><i class="bi bi-palette"></i><span class="nav-label">Design et apparence</span></a>
      <a href="{{ route('admin.updates.index') }}" class="{{ request()->routeIs('admin.updates.*') ? 'on' : '' }}"><i class="bi bi-megaphone"></i><span class="nav-label">Annonces et FAQ</span></a>
      <a href="{{ route('admin.content.edit') }}" class="{{ request()->routeIs('admin.content.*') ? 'on' : '' }}"><i class="bi bi-pencil-square"></i><span class="nav-label">Contenu du site</span></a>
      <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'on' : '' }}"><i class="bi bi-images"></i><span class="nav-label">Médiathèque</span></a>
      <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'on' : '' }}"><i class="bi bi-calendar-event"></i><span class="nav-label">Éditions</span></a>
      <a href="{{ route('admin.trash.index') }}" class="{{ request()->routeIs('admin.trash.*') ? 'on' : '' }}"><i class="bi bi-trash3"></i><span class="nav-label">Corbeille</span></a>
      <h4>Équipe</h4>
      <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'on' : '' }}"><i class="bi bi-journal-text"></i><span class="nav-label">Journal des actions</span></a>
      <a href="{{ route('admin.team.index') }}" class="{{ request()->routeIs('admin.team.*') ? 'on' : '' }}"><i class="bi bi-people-fill"></i><span class="nav-label">Comptes et accès</span></a>
      @endcan
      @can('manage-sensitive-data')<a href="{{ route('admin.backups.index') }}"><i class="bi bi-database-lock"></i><span class="nav-label">Sauvegardes et maintenance</span></a><a href="{{ route('admin.storage.index') }}"><i class="bi bi-hdd"></i><span class="nav-label">Fichiers inutilisés</span></a><a href="{{ route('admin.mail.edit') }}" class="{{ request()->routeIs('admin.mail.*') ? 'on' : '' }}"><i class="bi bi-envelope-gear"></i><span class="nav-label">Envoi des e-mails</span></a>@endcan
      <h4>Mon accès</h4>
      <a href="{{ route('admin.account.edit') }}" class="{{ request()->routeIs('admin.account.*') ? 'on' : '' }}"><i class="bi bi-shield-lock"></i><span class="nav-label">Mon compte</span></a>
    
      <a href="#rdsa-admin-guide" class="admin-guide-open" data-admin-guide-open aria-haspopup="dialog"><i class="bi bi-question-circle"></i><span class="nav-label">Guide d’utilisation</span></a>
