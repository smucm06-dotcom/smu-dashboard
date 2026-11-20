{{-- Used by the Add user and Edit user pages. --}}
<div class="um-page">
    <a class="um-back" href="{{ route('users.index') }}">
        <i data-lucide="arrow-left" aria-hidden="true"></i>
        Back to users
    </a>

    @if ($errors->any())
        <p class="um-message um-message--error" role="alert">Nothing was saved. Please correct the errors below.</p>
    @endif

    <form method="POST" action="{{ $action }}" autocomplete="off">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <section class="um-card">
            <h3>Account</h3>
            <p>
                @if ($user->exists)
                    Leave the password empty to keep the current one.
                @else
                    The user must choose a new password when they first log in.
                @endif
            </p>

            <div class="um-fields">
                <div class="um-field">
                    <label for="umName">Full name</label>
                    <input @class(['um-control', 'is-invalid' => $errors->has('name')]) id="umName" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
                    @error('name') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="um-field">
                    <label for="umEmail">Email address</label>
                    <input @class(['um-control', 'is-invalid' => $errors->has('email')]) id="umEmail" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="off" required>
                    @error('email') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="um-field">
                    <label for="umPassword">{{ $user->exists ? 'New password' : 'Password' }}</label>
                    <input @class(['um-control', 'is-invalid' => $errors->has('password')]) id="umPassword" type="password" name="password" autocomplete="new-password" @unless ($user->exists) required @endunless>
                    @error('password')
                        <span class="um-error" role="alert">{{ $message }}</span>
                    @else
                        <span class="um-hint">At least 8 characters, with letters and numbers.</span>
                    @enderror
                </div>

                <div class="um-field">
                    <label for="umPasswordConfirm">Confirm password</label>
                    <input class="um-control" id="umPasswordConfirm" type="password" name="password_confirmation" autocomplete="new-password" @unless ($user->exists) required @endunless>
                </div>
            </div>
        </section>

        <section class="um-card">
            <h3>Role and access</h3>
            <p>An administrator can open and manage everything. A department user belongs to one or more departments and can open only the initiatives chosen for them.</p>

            {{-- Sent as "access" (admin or departments), "departments[]" (department role names) and "permissions[]". --}}
            <fieldset class="um-roles-choice" @if ($isSelf) disabled @endif>
                <legend class="um-legend">Role</legend>
                <label class="um-radio">
                    <input type="radio" name="access" value="{{ \App\Http\Requests\UserRequest::ADMIN }}" @checked($selectedAccess === \App\Http\Requests\UserRequest::ADMIN)>
                    <span><strong>Administrator</strong><small>Opens and manages everything, including users.</small></span>
                </label>
                <label class="um-radio">
                    <input type="radio" name="access" value="{{ \App\Http\Requests\UserRequest::DEPARTMENTS }}" @checked($selectedAccess === \App\Http\Requests\UserRequest::DEPARTMENTS)>
                    <span><strong>Department user</strong><small>Opens only the initiatives chosen below, in one or more departments.</small></span>
                </label>
            </fieldset>
            @if ($isSelf)
                {{-- A disabled choice is not sent with the form. --}}
                <input type="hidden" name="access" value="{{ $savedAccess }}">
                @foreach ($savedDepartments as $department)
                    <input type="hidden" name="departments[]" value="{{ $department }}">
                @endforeach
                <span class="um-hint">You cannot change your own role.</span>
            @endif
            @error('access') <span class="um-error" role="alert">{{ $message }}</span> @enderror

            <div class="um-picker" id="umPicker" data-saved-access="{{ $savedAccess }}" data-saved-departments="{{ json_encode($savedDepartments) }}">
                <p class="um-picker-note" id="umNoteNone">Choose a role to see the departments and their initiatives.</p>
                <p class="um-picker-note" id="umNoteAdmin" hidden>Administrators can open and manage everything, so there is nothing to choose.</p>
                <p class="um-message um-message--notice" id="umNoteMoved" hidden></p>

                <div class="um-access-steps" id="umSteps" hidden>
                    {{-- Step 1: the departments the user belongs to --}}
                    <div class="um-picker-panel">
                        <div class="um-picker-tools">
                            <strong class="um-step-title">1. Departments</strong>
                            <input class="um-control um-picker-search" id="umDeptSearch" type="search" placeholder="Search departments" aria-label="Search departments" autocomplete="off">
                            <span class="um-picker-count" id="umDeptCount" aria-live="polite"></span>
                        </div>
                        <fieldset class="um-group um-dept-list" id="umDeptList" @if ($isSelf) disabled @endif>
                            <legend>Departments</legend>
                            @foreach ($roles->reject->isAdmin() as $role)
                                @php $initiativeCount = $role->department->initiatives->count(); @endphp
                                <label class="um-option" data-search="{{ \Illuminate\Support\Str::lower($role->label().' '.$role->department->code) }}">
                                    <input type="checkbox" name="departments[]" value="{{ $role->name }}" data-label="{{ $role->label() }}" @checked(in_array($role->name, $selectedDepartments, true))>
                                    <span class="um-option-text">
                                        <strong>{{ $role->label() }}</strong>
                                        <small>{{ $initiativeCount === 1 ? '1 initiative' : number_format($initiativeCount).' initiatives' }}</small>
                                    </span>
                                </label>
                            @endforeach
                            <p class="um-picker-empty" id="umDeptEmpty" hidden>No departments match your search.</p>
                        </fieldset>
                    </div>
                    @error('departments') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                    @error('departments.*') <span class="um-error" role="alert">{{ $message }}</span> @enderror

                    {{-- Step 2: the initiatives they can open, in each department ticked --}}
                    <div class="um-picker-panel um-initiatives">
                        <div class="um-picker-tools" id="umTools">
                            <strong class="um-step-title">2. Initiatives</strong>
                            <input class="um-control um-picker-search" id="umPickerSearch" type="search" placeholder="Search initiatives" aria-label="Search initiatives" autocomplete="off">
                            <span class="um-picker-count" id="umCount" aria-live="polite"></span>
                        </div>

                        <div class="um-chips" id="umChips" aria-label="Selected initiatives" hidden></div>

                        <p class="um-picker-empty" id="umNoteNoDepartment">Tick a department above to choose its initiatives.</p>

                        {{-- Only the ticked departments' groups are enabled, so only their choices are sent. --}}
                        <div class="um-groups">
                            @foreach ($roles->reject->isAdmin() as $role)
                                <fieldset class="um-group" data-role="{{ $role->name }}" hidden disabled>
                                    <legend>Initiatives of {{ $role->label() }}</legend>
                                    <div class="um-group-head">
                                        <strong>{{ $role->label() }}</strong>
                                        @if ($role->department->initiatives->isNotEmpty())
                                            <label class="um-select-all">
                                                <input type="checkbox" class="um-group-all" aria-label="Select all initiatives of {{ $role->label() }}">
                                                Select all
                                            </label>
                                        @endif
                                        <span class="um-picker-count um-group-count"></span>
                                    </div>

                                    @foreach ($role->department->initiatives as $initiative)
                                        <label class="um-option" data-search="{{ \Illuminate\Support\Str::lower($initiative->shortName().' '.$initiative->name) }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $initiative->permission->id }}" data-label="{{ $initiative->shortName() }}" @checked(in_array($initiative->permission->id, $selectedPermissions, true))>
                                            <span class="um-option-text">
                                                <strong>{{ $initiative->shortName() }}</strong>
                                                <small>{{ $initiative->name }}</small>
                                            </span>
                                            @unless ($initiative->is_active)
                                                <span class="um-badge um-badge--muted">Inactive</span>
                                            @endunless
                                        </label>
                                    @endforeach

                                    <p class="um-picker-empty" data-empty="{{ $role->department->initiatives->isEmpty() ? 'always' : 'search' }}" @unless ($role->department->initiatives->isEmpty()) hidden @endunless>
                                        {{ $role->department->initiatives->isEmpty() ? 'This department has no initiatives yet.' : 'No initiatives match your search.' }}
                                    </p>
                                </fieldset>
                            @endforeach
                        </div>
                    </div>
                </div>

                @error('permissions') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                @error('permissions.*') <span class="um-error" role="alert">{{ $message }}</span> @enderror
            </div>
        </section>

        <div class="um-form-actions">
            <a class="um-btn" href="{{ route('users.index') }}">Cancel</a>
            <button class="um-btn um-btn--primary" type="submit">{{ $submitLabel }}</button>
        </div>
    </form>
</div>
