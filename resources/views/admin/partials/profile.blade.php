    <div class="me">
      <div class="av">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</div>
      <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->is_admin ? 'Administrateur' : 'Contrôleur des billets' }}</small></div>
    </div>
