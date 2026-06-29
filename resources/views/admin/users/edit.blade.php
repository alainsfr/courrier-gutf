<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h1 class="h4 mb-0">{{ __('users.edit_title') }}</h1>
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">{{ __('users.back') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">{{ __('users.form_error') }}</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('users.name') }}</label>
                                <input class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('users.email') }}</label>
                                <input class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('users.locale') }}</label>
                                <select class="form-select" name="locale" required>
                                    <option value="fr" @selected(old('locale', $user->locale)==='fr')>FR</option>
                                    <option value="en" @selected(old('locale', $user->locale)==='en')>EN</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('users.role') }}</label>
                                <select class="form-select" name="role" required>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->name }}" @selected(old('role', $currentRole)===$r->name)>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ __('users.status') }}</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" @selected(old('is_active', (string)(int)$user->is_active)==='1')>{{ __('users.active') }}</option>
                                    <option value="0" @selected(old('is_active', (string)(int)$user->is_active)==='0')>{{ __('users.inactive') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button class="btn btn-gutf" type="submit">{{ __('users.save') }}</button>
                            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">{{ __('users.cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>{{ __('users.reset_password') }}</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.users.reset_password', $user) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label">{{ __('users.new_password') }}</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('users.password_confirm') }}</label>
                            <input type="password" class="form-control" name="new_password_confirmation" required>
                        </div>

                        <button class="btn btn-outline-dark" type="submit">{{ __('users.reset_password_btn') }}</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <strong>{{ __('users.quick_actions') }}</strong>
                </div>
                <div class="card-body d-grid gap-2">
                    <form method="POST" action="{{ route('admin.users.toggle_active', $user) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-outline-danger" type="submit">
                            {{ $user->is_active ? __('users.deactivate') : __('users.activate') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
