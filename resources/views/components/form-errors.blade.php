@props([
    'title' => null,
    'bags' => [],
])

@php
    $messages = collect($errors->all());

    foreach ((array) $bags as $bag) {
        $messages = $messages->merge($errors->{$bag}->all());
    }

    $messages = $messages->unique()->values();
@endphp

@if ($messages->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'ui-alert ui-alert-error mb-4', 'role' => 'alert']) }}>
        <p class="mb-1 font-semibold">{{ $title ?? __('app.form_error_title') }}</p>
        <ul class="mb-0 list-disc pl-4">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
