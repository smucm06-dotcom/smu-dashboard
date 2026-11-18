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
            <p>The role is the user's department. Then choose the initiatives of that department they can open.</p>

            <div class="um-field">
                <label for="umRole">Role</label>
                <select @class(['um-control', 'is-invalid' => $errors->has('role')]) id="umRole" @if ($isSelf) disabled @else name="role" required @endif data-saved-role="{{ $savedRole }}">
                    <option value="" @selected(! $selectedRole)>Choose a role</option>
                    @foreach ($roles->filter->isAdmin() as $role)
                        <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->label() }}</option>
                    @endforeach
                    <optgroup label="Departments">
                        @foreach ($roles->reject->isAdmin() as $role)
                            <option value="{{ $role->name }}" @selected($selectedRole === $role->name)>{{ $role->label() }}</option>
                        @endforeach
                    </optgroup>
                </select>
                @if ($isSelf)
                    {{-- A disabled select is not sent with the form. --}}
                    <input type="hidden" name="role" value="{{ $savedRole }}">
                    <span class="um-hint">You cannot change your own role.</span>
                @endif
                @error('role') <span class="um-error" role="alert">{{ $message }}</span> @enderror
            </div>

            <div class="um-picker" id="umPicker">
                <p class="um-picker-note" id="umNoteNone">Choose a role to see its initiatives.</p>
                <p class="um-picker-note" id="umNoteAdmin" hidden>Administrators can open and manage everything, so there is nothing to choose.</p>
                <p class="um-message um-message--notice" id="umNoteMoved" hidden>
                    Saving will remove this user's access to their current department's initiatives.
                </p>

                <div class="um-picker-panel" id="umPanel" hidden>
                    <div class="um-picker-tools" id="umTools">
                        <input class="um-control um-picker-search" id="umPickerSearch" type="search" placeholder="Search initiatives" aria-label="Search initiatives" autocomplete="off">
                        <label class="um-select-all">
                            <input type="checkbox" id="umSelectAll">
                            Select all
                        </label>
                        <span class="um-picker-count" id="umCount" aria-live="polite"></span>
                    </div>

                    <div class="um-chips" id="umChips" aria-label="Selected initiatives" hidden></div>

                    {{-- Only the chosen department's group is enabled, so only its choices are sent. --}}
                    @foreach ($roles->reject->isAdmin() as $role)
                        <fieldset class="um-group" data-role="{{ $role->name }}" hidden disabled>
                            <legend>Initiatives of {{ $role->label() }}</legend>

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
