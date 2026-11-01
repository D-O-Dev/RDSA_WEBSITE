<button type="button" class="questions-float" data-questions-open data-announcements-url="{{ route('public.information') }}" aria-haspopup="dialog" aria-controls="questions-dialog" aria-label="Informations et questions pour l’équipe RDSA"><i class="bi bi-chat-dots"></i><span>{{ $announcements->isNotEmpty() ? 'Infos & questions' : 'Une question ?' }}</span>@if($announcements->isNotEmpty())<span class="questions-info-count" aria-label="{{ $announcements->count() }} informations disponibles">{{ $announcements->count() }}</span>@endif</button>
<dialog id="questions-dialog" class="questions-dialog" aria-labelledby="questions-title">
<div class="questions-dialog-head"><div><span class="section-kicker">L’ÉQUIPE RDSA</span><h2 id="questions-title">On t’écoute.</h2></div><button type="button" data-dialog-close aria-label="Fermer le formulaire" autofocus><i class="bi bi-x-lg"></i></button></div>
@if($announcements->isNotEmpty())
<div class="questions-announcements" aria-labelledby="questions-announcements-title"><h3 id="questions-announcements-title"><i class="bi bi-megaphone" aria-hidden="true"></i>Les informations de l’équipe</h3>@foreach($announcements as $announcement)<article class="questions-announcement"><h4><span class="unread-dot" role="img" aria-label="Annonce non lue"></span>{{ $announcement->title }}</h4><p>{{ $announcement->body }}</p></article>@endforeach</div>
@endif
@if($faqs->isNotEmpty())
<details class="questions-faq"><summary><span><i class="bi bi-question-circle" aria-hidden="true"></i>Questions fréquentes</span><span>{{ $faqs->count() }} {{ $faqs->count() === 1 ? 'réponse' : 'réponses' }} <i class="bi bi-chevron-down" aria-hidden="true"></i></span></summary><div class="questions-faq-list">@foreach($faqs as $faq)<details class="questions-faq-item"><summary>{{ $faq->title }}<i class="bi bi-plus-lg" aria-hidden="true"></i></summary><p>{{ $faq->body }}</p></details>@endforeach</div></details>
@endif
<p class="questions-dialog-intro">Écris-nous. Après l’envoi, conserve ton lien privé pour retrouver nos réponses.</p>
<form class="questions-form" data-contact-form method="POST" action="{{ route('messages.store') }}">
@csrf<input type="hidden" name="submission_token" value="{{ $contactSubmissionToken }}">
<div class="questions-honey" aria-hidden="true"><label>Site internet<input name="website" tabindex="-1" autocomplete="off"></label></div>
<div class="row g-3">
<div class="col-12"><label for="question-name">Ton nom</label><input id="question-name" class="form-control" name="name" value="{{ old('name') }}" minlength="2" maxlength="150" autocomplete="name" required></div>
<div class="col-sm-6"><label for="question-email">Adresse e-mail</label><input id="question-email" class="form-control" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"></div>
<div class="col-sm-6"><label for="question-phone">Téléphone / WhatsApp</label><input id="question-phone" class="form-control" name="phone" type="tel" value="{{ old('phone') }}" maxlength="30" autocomplete="tel"></div>
<div class="col-12"><p class="questions-help">Renseigne au moins un moyen de te contacter.</p><label for="question-subject">Ta question concerne…</label><input id="question-subject" class="form-control" name="subject" value="{{ old('subject') }}" placeholder="Mon billet, le programme, le lieu…" minlength="3" maxlength="150" required></div>
<div class="col-12"><label for="question-body">Ton message</label><textarea id="question-body" class="form-control" name="body" rows="4" minlength="10" maxlength="5000" required>{{ old('body') }}</textarea></div>
<div class="col-12"><div class="questions-feedback" data-form-feedback role="status" aria-live="polite">{{ $errors->first('subject') ?: $errors->first('body') ?: $errors->first('submission_token') }}</div><button class="questions-submit" type="submit">Envoyer mon message <i class="bi bi-arrow-up-right"></i></button></div>
</div></form>
</dialog>
