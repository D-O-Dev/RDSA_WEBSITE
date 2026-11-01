@php($type = $field['type'] ?? 'text')
<div class="f content-field">
<label for="content-{{ $key }}">{{ $field['label'] }}</label>
@if($type === 'textarea')
<textarea class="in" id="content-{{ $key }}" name="{{ $key }}" rows="3" maxlength="2000" required @if($errors->has($key)) aria-invalid="true" aria-describedby="error-{{ $key }}" @endif>{{ old($key, $content[$key]) }}</textarea>
@elseif($type === 'image')
@include('admin.partials.image-source', ['inputId' => 'content-' . $key, 'inputName' => $key, 'imageValue' => old($key, $content[$key]), 'required' => true])
@else
<input class="in" id="content-{{ $key }}" name="{{ $key }}" value="{{ old($key, $content[$key]) }}" maxlength="500" required @if($errors->has($key)) aria-invalid="true" aria-describedby="error-{{ $key }}" @endif>
@endif
@error($key)<div class="content-field-error" id="error-{{ $key }}" role="alert">{{ $message }}</div>@enderror
</div>
