@php($type = $field['type'] ?? 'text')
<div class="f content-field">
<label for="content-{{ $key }}">{{ $field['label'] }}</label>
@if($type === 'textarea')
<textarea class="in" id="content-{{ $key }}" name="{{ $key }}" rows="3" maxlength="2000" required @if($errors->has($key)) aria-invalid="true" aria-describedby="error-{{ $key }}" @endif>{{ old($key, $content[$key]) }}</textarea>
@elseif($type === 'image')
<div class="content-current-logo"><img src="{{ asset($content[$key]) }}" alt="Logo actuel de la RDSA" data-content-logo-preview></div>
<details class="content-image-picker"><summary class="btn"><i class="bi bi-images" aria-hidden="true"></i>Choisir une autre image</summary>
<div class="content-image-options" role="group" aria-label="Choisir le logo de la RDSA">
@foreach($images as $image)
<label class="content-image-option"><input id="{{ $loop->first ? 'content-' . $key : 'logo-choice-' . $loop->index }}" type="radio" name="{{ $key }}" value="{{ $image }}" data-content-logo data-src="{{ asset($image) }}" @checked(old($key, $content[$key]) === $image) required><img src="{{ asset($image) }}" alt="Image {{ $loop->iteration }}" loading="lazy"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></label>
@endforeach
</div>
</details>
@else
<input class="in" id="content-{{ $key }}" name="{{ $key }}" value="{{ old($key, $content[$key]) }}" maxlength="500" required @if($errors->has($key)) aria-invalid="true" aria-describedby="error-{{ $key }}" @endif>
@endif
@error($key)<div class="content-field-error" id="error-{{ $key }}" role="alert">{{ $message }}</div>@enderror
</div>
