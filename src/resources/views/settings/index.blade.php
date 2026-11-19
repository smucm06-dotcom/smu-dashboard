@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="um-page">
    <div class="um-narrow">
        @if ($mustChange)
            <p class="um-message um-message--notice" role="status">Please choose a new password before you continue. The administrator knows the current one.</p>
        @endif

        @if (session('passwordChanged'))
            <p class="um-message um-message--ok" role="status">
                <i data-lucide="circle-check" aria-hidden="true"></i>
                {{ session('passwordChanged') }}
            </p>
        @endif

        <form class="um-card" method="POST" action="{{ route('settings.password.update') }}">
            @csrf
            @method('PUT')

            <h3>Change password</h3>
            <p>At least 8 characters, with letters and numbers.</p>

            <div class="um-fields um-fields--single">
                <div class="um-field">
                    <label for="umCurrentPassword">Current password</label>
                    <div class="um-password">
                        <input @class(['um-control', 'is-invalid' => $errors->has('current_password')]) id="umCurrentPassword" type="password" name="current_password" autocomplete="current-password" required>
                        @include('settings.partials.show-password', ['for' => 'umCurrentPassword'])
                    </div>
                    @error('current_password') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="um-field">
                    <label for="umNewPassword">New password</label>
                    <div class="um-password">
                        <input @class(['um-control', 'is-invalid' => $errors->has('password')]) id="umNewPassword" type="password" name="password" autocomplete="new-password" required>
                        @include('settings.partials.show-password', ['for' => 'umNewPassword'])
                    </div>
                    @error('password') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="um-field">
                    <label for="umConfirmPassword">Confirm new password</label>
                    <div class="um-password">
                        <input class="um-control" id="umConfirmPassword" type="password" name="password_confirmation" autocomplete="new-password" required>
                        @include('settings.partials.show-password', ['for' => 'umConfirmPassword'])
                    </div>
                </div>
            </div>

            <div class="um-form-actions">
                <button class="um-btn um-btn--primary" type="submit">Change password</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    @include('users.partials.styles')
    <style>
        /* An eye button inside each password box shows or hides what is typed. */
        .um-password { position: relative; }
        .um-password .um-control { padding-right: 44px; }
        .um-password-toggle {
            position: absolute;
            top: 50%;
            right: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            padding: 0;
            color: var(--muted);
            background: none;
            border: 0;
            border-radius: 7px;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .um-password-toggle:hover { color: var(--primary); background: var(--primary-light); }
        .um-password-toggle:focus-visible { outline: 2px solid var(--primary); outline-offset: 1px; }
        .um-password-toggle svg { width: 16px; height: 16px; }
        .um-password-toggle .um-eye-off, .um-password-toggle.is-shown .um-eye { display: none; }
        .um-password-toggle.is-shown .um-eye-off { display: inline; }
        /* Browsers that add their own reveal button would show two. */
        .um-password input::-ms-reveal, .um-password input::-ms-clear { display: none; }
    </style>
@endpush

@push('scripts')
<script>
    // Shows what is typed in a password box while its eye is pressed, and hides it again on the next press.
    document.querySelectorAll('[data-show-password]').forEach(function (button) {
        const input = document.getElementById(button.dataset.showPassword);

        button.addEventListener('click', function () {
            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';
            button.classList.toggle('is-shown', show);
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.title = show ? 'Hide password' : 'Show password';
            input.focus();
        });
    });

    // A password is never sent while it is showing as plain text, so the browser treats it as a password.
    document.querySelectorAll('.um-password').forEach(function (box) {
        box.closest('form').addEventListener('submit', function () {
            box.querySelector('input').type = 'password';
        });
    });
</script>
@endpush
