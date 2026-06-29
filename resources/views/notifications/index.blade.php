<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h1 class="h4 mb-0">{{ __('notifications.title') }}</h1>

            <form method="POST" action="{{ route('notifications.read_all') }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-outline-secondary" type="submit">
                    {{ __('notifications.mark_all') }}
                </button>
            </form>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ __('notifications.unread') }}</strong>
                </div>
                <div class="card-body">
                    @forelse($unread as $n)
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-semibold">{{ $n->data['title'] ?? '—' }}</div>
                            <div class="text-muted small">{{ $n->data['message'] ?? '' }}</div>

                            <div class="d-flex gap-2 mt-2">
                                @if(!empty($n->data['url']))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ $n->data['url'] }}">
                                        {{ __('notifications.open') }}
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-dark" type="submit">
                                        {{ __('notifications.mark_read') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">{{ __('notifications.none') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-3">
                {{ $unread->links() }}
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ __('notifications.read') }}</strong>
                </div>
                <div class="card-body">
                    @forelse($read as $n)
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-semibold">{{ $n->data['title'] ?? '—' }}</div>
                            <div class="text-muted small">{{ $n->data['message'] ?? '' }}</div>

                            @if(!empty($n->data['url']))
                                <div class="mt-2">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ $n->data['url'] }}">
                                        {{ __('notifications.open') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">{{ __('notifications.none') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-3">
                {{ $read->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
