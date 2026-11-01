@php([$heroLead, $heroAccent] = \App\Models\SiteContent::heading($edition?->theme ?: 'Une rencontre dans Sa présence'))
<div class="ref-edition"><span class="ref-spark" aria-hidden="true"></span>{{ $edition?->name ?? 'RDSA' }} <span>· PRIÈRE & ADORATION</span></div>
<h1 class="ref-title"><span>{{ $heroLead }}</span> <em>{{ $heroAccent }}</em></h1>
<p class="ref-description">{{ $siteContent['hero_subtitle'] }}<br>{{ $siteContent['hero_subtitle_gold'] }}</p>
<div class="ref-actions"><a class="ref-primary" href="#apropos">Découvrir la rencontre <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a><button class="ref-secondary" type="button" data-ticket-open><span><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span>Mon billet</button></div>
