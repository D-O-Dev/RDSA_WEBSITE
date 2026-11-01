<div class="f">
<label for="{{ $prefix }}-{{ $field }}">{{ $label }}</label>
<div class="account-password-field"><input class="in" id="{{ $prefix }}-{{ $field }}" type="password" name="{{ $field }}" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}" required @if($field !== 'current_password') minlength="12" @endif @if($errors->getBag($bag)->has($field)) aria-invalid="true" aria-describedby="{{ $prefix }}-{{ $field }}-error" @elseif($field === 'password') aria-describedby="account-password-help" @endif><button class="ib" type="button" data-account-password="{{ $prefix }}-{{ $field }}" aria-label="Afficher : {{ $label }}" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
@error($field, $bag)<p class="hint" id="{{ $prefix }}-{{ $field }}-error" role="alert">{{ $message }}</p>@enderror
</div>
