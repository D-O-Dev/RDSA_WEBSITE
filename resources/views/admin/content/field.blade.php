@php($type = $field['type'] ?? 'text')
<div class="f content-field">
<label for="content-{{ $key }}">{{ $field['label'] }}</label>
@if($type === 'boolean')
<input type="hidden" name="{{ $key }}" value="0"><label class="sw"><span>{{ $field['label'] }}</span><input type="checkbox" id="content-{{ $key }}" name="{{ $key }}" value="1" @checked(old($key, $content[$key]))></label>
@elseif($type === 'section_order')
<div data-section-order>@foreach(explode(',', old($key, $content[$key])) as $section)<label class="sw"><span>{{ ['rdsa' => 'Présentation', 'editions' => 'Éditions', 'medias' => 'Galerie', 'participer' => 'Réservations', 'contact' => 'Contact'][$section] ?? $section }}</span><select class="sel" name="section_positions[{{ $section }}]" aria-label="Position de {{ $section }}">@for($position = 1; $position <= 5; $position++)<option value="{{ $position }}" @selected($loop->iteration === $position)>{{ $position }}</option>@endfor</select></label>@endforeach</div><input type="hidden" name="{{ $key }}" value="{{ old($key, $content[$key]) }}">
@elseif($type === 'textarea')
<textarea class="in" id="content-{{ $key }}" name="{{ $key }}" rows="3" maxlength="2000" required @if($errors->has($key)) aria-invalid="true" aria-describedby="error-{{ $key }}" @endif>{{ old($key, $content[$key]) }}</textarea>
@elseif($type === 'image')
@include('admin.partials.image-source', ['inputId' => 'content-' . $key, 'inputName' => $key, 'imageValue' => old($key, $content[$key]), 'required' => true])
@else
<input class="in" id="content-{{ $key }}" name="{{ $key }}" value="{{ old($key, $content[$key]) }}" maxlength="500" @required(!($field['optional'] ?? false)) @if($errors->has($key)) aria-invalid="true" aria-describedby="error-{{ $key }}" @endif>
@endif
@error($key)<div class="content-field-error" id="error-{{ $key }}" role="alert">{{ $message }}</div>@enderror
</div>
