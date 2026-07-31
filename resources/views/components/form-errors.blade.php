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
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800', 'role' => 'alert']) }}>
        <p class="mb-1 font-semibold">{{ $title ?? __('app.form_error_title') }}</p>
        <ul class="mb-0 list-disc pl-4">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
