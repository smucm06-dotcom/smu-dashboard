@extends('layouts.app')

@section('title', 'Users')

@section('content')
@php
    $hasFilters = $filters['search'] !== '' || $filters['role'] !== '' || $filters['status'] !== '';
@endphp

<div class="um-page">
    <div class="um-head">
        <div>
            <h3>All users</h3>
            <p>Each user belongs to one department and can open only the initiatives given to them.</p>
        </div>

        <a class="um-btn um-btn--primary" href="{{ route('users.create') }}">
            <i data-lucide="user-plus" aria-hidden="true"></i>
            Add user
        </a>
    </div>

    @if (session('userSaved'))
        <p class="um-message um-message--ok" role="status">
            <i data-lucide="circle-check" aria-hidden="true"></i>
            {{ session('userSaved') }}
        </p>
    @endif

    @error('status')
        <p class="um-message um-message--error" role="alert">{{ $message }}</p>
    @enderror

    <form class="um-filters" method="GET" action="{{ route('users.index') }}">
        <div class="um-filter-search">
            <label for="umSearch">Search</label>
            <input class="um-control" id="umSearch" type="search" name="search" value="{{ $filters['search'] }}" maxlength="100" placeholder="Name or email">
        </div>

        <div>
            <label for="umRoleFilter">Role</label>
            <select class="um-control" id="umRoleFilter" name="role" onchange="this.form.submit()">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected($filters['role'] === $role->name)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="umStatusFilter">Status</label>
            <select class="um-control" id="umStatusFilter" name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                <option value="disabled" @selected($filters['status'] === 'disabled')>Disabled</option>
            </select>
        </div>

        <div class="um-filter-actions">
            <button class="um-btn" type="submit">Search</button>
            @if ($hasFilters)
                <a class="um-btn" href="{{ route('users.index') }}">Clear</a>
            @endif
        </div>
    </form>

    <div class="um-table-card">
        @if ($users->isEmpty())
            <p class="um-empty">{{ $hasFilters ? 'No users match these filters.' : 'No users have been added yet.' }}</p>
        @else
            <table class="um-table">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Role or departments</th>
                        <th scope="col">Can open</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="um-sr">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $listedUser)
                        @php
                            // Administrator, or each department the user belongs to, by name.
                            $userRoles = $listedUser->roles->sortBy(fn ($role) => [$role->isAdmin() ? 0 : 1, $role->label()])->values();
                            $isAdminUser = $userRoles->contains(fn ($role) => $role->isAdmin());
                            $access = $listedUser->permissions->map(fn ($permission) => $permission->initiative?->shortName())->filter()->sort()->values();
                            $isSelf = $listedUser->is(auth()->user());
                        @endphp
                        <tr>
                            <td class="um-cell-user" data-label="User">
                                <div class="um-user">
                                    <span class="um-avatar" aria-hidden="true">{{ strtoupper(mb_substr($listedUser->name, 0, 1)) }}</span>
                                    <div class="um-user-text">
                                        <strong>{{ $listedUser->name }}@if ($isSelf) <em class="um-you">(you)</em>@endif</strong>
                                        <span>{{ $listedUser->email }}</span>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Role or departments">
                                <div class="um-roles">
                                    @forelse ($userRoles as $role)
                                        <span @class(['um-badge', 'um-badge--admin' => $role->isAdmin()])>{{ $role->label() }}</span>
                                    @empty
                                        <span class="um-muted">No role</span>
                                    @endforelse
                                </div>
                            </td>

                            <td data-label="Can open">
                                <div class="um-access" @if ($access->count() > 3) title="{{ $access->implode(', ') }}" @endif>
                                    @if ($isAdminUser)
                                        <span class="um-muted">Everything</span>
                                    @elseif ($access->isEmpty())
                                        <span class="um-muted">Nothing yet</span>
                                    @else
                                        @foreach ($access->take(3) as $name)
                                            <span class="um-badge um-badge--muted">{{ $name }}</span>
                                        @endforeach
                                        @if ($access->count() > 3)
                                            <span class="um-badge um-badge--muted">+{{ $access->count() - 3 }} more</span>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <td data-label="Status">
                                <div>
                                    <span class="um-status">
                                        <span @class(['um-status-dot', 'is-off' => ! $listedUser->is_active])></span>
                                        {{ $listedUser->is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                    @if ($listedUser->is_active && $listedUser->must_change_password)
                                        <span class="um-status-note">Must change password</span>
                                    @endif
                                </div>
                            </td>

                            <td class="um-cell-actions" data-label="Actions">
                                <div class="um-row-actions">
                                    <a class="um-btn um-btn--small" href="{{ route('users.edit', $listedUser) }}" aria-label="Edit {{ $listedUser->name }}">
                                        <i data-lucide="pencil" aria-hidden="true"></i>
                                        Edit
                                    </a>

                                    @unless ($isSelf)
                                        <form method="POST" action="{{ route('users.status', $listedUser) }}" data-um-confirm="{{ $listedUser->is_active ? 'Disable '.$listedUser->name.'? They will be logged out and cannot log in until enabled again.' : 'Enable '.$listedUser->name.'?' }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="active" value="{{ $listedUser->is_active ? 0 : 1 }}">
                                            <button type="submit" @class(['um-btn', 'um-btn--small', 'um-btn--danger' => $listedUser->is_active]) aria-label="{{ $listedUser->is_active ? 'Disable' : 'Enable' }} {{ $listedUser->name }}">
                                                <i data-lucide="{{ $listedUser->is_active ? 'user-x' : 'user-check' }}" aria-hidden="true"></i>
                                                {{ $listedUser->is_active ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($users->total() > 0)
        <div class="um-pagination">
            <p>Showing {{ number_format($users->firstItem()) }} to {{ number_format($users->lastItem()) }} of {{ number_format($users->total()) }} {{ \Illuminate\Support\Str::plural('user', $users->total()) }}</p>

            @if ($users->hasPages())
                <div class="um-pagination-links">
                    @if ($users->onFirstPage())
                        <span class="um-btn um-btn--small is-disabled" aria-disabled="true">Previous</span>
                    @else
                        <a class="um-btn um-btn--small" href="{{ $users->previousPageUrl() }}" rel="prev">Previous</a>
                    @endif

                    <span>Page {{ number_format($users->currentPage()) }} of {{ number_format($users->lastPage()) }}</span>

                    @if ($users->hasMorePages())
                        <a class="um-btn um-btn--small" href="{{ $users->nextPageUrl() }}" rel="next">Next</a>
                    @else
                        <span class="um-btn um-btn--small is-disabled" aria-disabled="true">Next</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

@push('styles')
@include('users.partials.styles')
@endpush

@push('scripts')
<script>
    document.querySelectorAll('[data-um-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (! window.confirm(form.dataset.umConfirm)) {
                event.preventDefault();
            }
        });
    });
</script>
@endpush
