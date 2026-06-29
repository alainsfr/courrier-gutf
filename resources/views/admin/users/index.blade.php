<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h1 class="h4 mb-0">{{ __('users.title') }}</h1>
            <a class="btn btn-gutf" href="{{ route('admin.users.create') }}">{{ __('users.new') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">{{ __('users.search') }}</label>
                    <input name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('users.search_placeholder') }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" type="submit">{{ __('users.apply') }}</button>
                </div>
                <div class="col-md-3">
                    <a class="btn btn-outline-danger w-100" href="{{ route('admin.users.index') }}">{{ __('users.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('users.name') }}</th>
                        <th>{{ __('users.email') }}</th>
                        <th>{{ __('users.role') }}</th>
                        <th>{{ __('users.locale') }}</th>
                        <th>{{ __('users.status') }}</th>
                        <th class="text-end">{{ __('users.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td class="fw-semibold">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->getRoleNames()->first() ?? '—' }}</td>
                            <td>{{ strtoupper($u->locale ?? 'fr') }}</td>
                            <td>
                                @if($u->is_active)
                                    <span class="badge text-bg-success">{{ __('users.active') }}</span>
                                @else
                                    <span class="badge text-bg-danger">{{ __('users.inactive') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.users.edit', $u) }}">
                                    {{ __('users.edit') }}
                                </a>
                                <form method="POST" action="{{ route('admin.users.toggle_active', $u) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-outline-dark btn-sm" type="submit">
                                        {{ $u->is_active ? __('users.deactivate') : __('users.activate') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('users.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
</x-app-layout>
