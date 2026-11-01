<div class="image-source-editor" data-image-source>
<input class="in" id="{{ $inputId }}" name="{{ $inputName }}" value="{{ $imageValue }}" maxlength="2000" placeholder="https://…/image.jpg" data-image-address @if($required ?? false) required @endif>
<p class="hint">Colle l’adresse HTTPS directe de l’image, pas le lien d’une publication. Choisis une adresse durable qui reste accessible publiquement.</p>
@if(!str_contains($inputName, '['))<label class="hint">Ou téléverser une image JPG, PNG ou WebP (5 Mo maximum)<input class="in" type="file" name="images[{{ $inputName }}]" accept="image/jpeg,image/png,image/webp"></label>@endif
<div class="content-current-logo" @if(!$imageValue) hidden @endif><img src="{{ $imageValue ? asset($imageValue) : '' }}" alt="Aperçu de l’image" data-image-preview referrerpolicy="no-referrer"></div>
<p class="hint" data-image-error hidden role="status">L’image ne se charge pas. Vérifie son adresse et son accès public.</p>
<details class="content-image-picker"><summary class="btn">Choisir une image déjà présente</summary><div class="content-image-options">
@foreach($images as $image)<button class="content-image-option" type="button" data-image-choice="{{ $image }}" aria-label="Choisir l’image {{ $loop->iteration }}"><img src="{{ asset($image) }}" alt="Image {{ $loop->iteration }}" loading="lazy"></button>@endforeach
</div></details></div>
