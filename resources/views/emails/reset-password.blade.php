<x-mail::message>
# {{ $greeting }}

{{ $intro }}

<x-mail::button :url="$url">
{{ $action }}
</x-mail::button>

{{ $expires }}

{{ $ignore }}

{{ $signature }}

<x-slot:subcopy>
{{ $fallback }} [{{ $url }}]({{ $url }})
</x-slot:subcopy>
</x-mail::message>
