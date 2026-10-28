@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false, 'placeholder' => null])
<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $label }} @if($required)<span class="text-rose-600" aria-hidden="true">*</span>@endif</label>
    @if($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="3" class="courrier-input" @required($required) placeholder="{{ $placeholder }}" @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" class="courrier-input" @required($required) placeholder="{{ $placeholder }}" {{ $attributes->except('class') }} @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif>
    @endif
    @error($name)<p id="{{ $name }}-error" class="mt-1.5 mb-0 text-xs text-rose-700">{{ $message }}</p>@enderror
</div>
