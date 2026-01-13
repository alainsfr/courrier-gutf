<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="h4 mb-0">{{ __('app.dashboard') }}</h1>
            <div class="text-muted small">
                {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="h5 mb-1">{{ __('app.welcome', ['name' => Auth::user()->name]) }}</div>
                    <div class="text-muted">{{ __('app.dashboard_subtitle') }}</div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm">
                        {{ __('app.my_profile') }}
                    </a>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100">
                        <div class="text-muted small mb-1">{{ __('app.kpi_incoming') }}</div>
                        <div class="h4 mb-0">0</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100">
                        <div class="text-muted small mb-1">{{ __('app.kpi_in_progress') }}</div>
                        <div class="h4 mb-0">0</div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100">
                        <div class="text-muted small mb-1">{{ __('app.kpi_archived') }}</div>
                        <div class="h4 mb-0">0</div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border mt-4 mb-0">
                <div class="fw-semibold mb-1">{{ __('app.next_steps_title') }}</div>
                <ul class="mb-0">
                    <li>{{ __('app.next_steps_1') }}</li>
                    <li>{{ __('app.next_steps_2') }}</li>
                    <li>{{ __('app.next_steps_3') }}</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
