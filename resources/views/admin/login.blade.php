@include('admin.head')

<body>
  @include('partials.entry-loader', ['entryLabel' => 'Bienvenue dans l’espace RDSA'])
  <section id="login" aria-label="Connexion">
    <div class="lg-l">
      <div class="brand">
        <img src="{{ asset(\App\Models\SiteContent::current()['logo_path']) }}" alt="RDSA" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'">
        <div class="brand-fb">R</div>
        <div><b>RDSA</b><small>Espace d'administration</small></div>
      </div>
      <div class="lg-w">
        <h2>Bon retour</h2>
        <p>Content de te revoir.<br>Reprenons là où tu t'étais arrêté.</p>
      </div>
      <small>© {{ now()->year }} RDSA · Développé par Jada Firmament.</small>
    </div>
    <div class="lg-r">
      <form class="lg-c" method="POST" action="{{ route('admin.login') }}">@csrf
        <div class="lg-logo"><img src="{{ asset(\App\Models\SiteContent::current()['logo_path']) }}" alt="RDSA"
            onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi bi-shield-lock'}))">
        </div>
        <h2>Connexion</h2>@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
        <p>Connecte-toi pour accéder à l'administration</p>
        <div class="lg-f"><label for="lgMail">Adresse e-mail</label><i class="bi bi-envelope"></i><input class="in"
            id="lgMail" name="email" value="{{ old('email') }}" required type="email" placeholder="vous@exemple.com" autocomplete="username"></div>
        <div class="lg-f"><label for="lgPass">Mot de passe</label><i class="bi bi-lock"></i><input class="in"
            id="lgPass" name="password" required type="password" placeholder="••••••••" autocomplete="current-password"><button class="eye"
            id="eye" type="button" aria-label="Afficher le mot de passe"><i class="bi bi-eye"
              style="position:static"></i></button></div>

        <div id="lgErr" role="alert">{{ $errors->first() }}</div>
        <details style="margin:14px 0"><summary>J’utilise la double authentification</summary><div class="f"><label for="login-otp">Code temporaire ou code de secours</label><input class="in" id="login-otp" name="two_factor_code" autocomplete="one-time-code" maxlength="32"></div></details><button class="lg-b" id="lgBtn" type="submit">Se connecter<i class="bi bi-arrow-right"></i></button>
        <a class="btn" href="{{ route('password.request') }}">Mot de passe oublié ?</a><div class="lg-s"><i class="bi bi-shield-check"></i> Accès réservé aux administrateurs. Vos données sont
          protégées.</div>
      </form>
    </div>
  </section>
  <script>
    document.getElementById('eye').onclick = () => {
      const input = document.getElementById('lgPass');
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      document.querySelector('#eye i').className = 'bi bi-eye' + (show ? '-slash' : '');
    };
  </script>
</body>

</html>