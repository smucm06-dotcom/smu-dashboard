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
                    <input @class(['um-control', 'is-invalid' => $errors->has('current_password')]) id="umCurrentPassword" type="password" name="current_password" autocomplete="current-password" required>
                    @error('current_password') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="um-field">
                    <label for="umNewPassword">New password</label>
                    <input @class(['um-control', 'is-invalid' => $errors->has('password')]) id="umNewPassword" type="password" name="password" autocomplete="new-password" required>
                    @error('password') <span class="um-error" role="alert">{{ $message }}</span> @enderror
                </div>

                <div class="um-field">
                    <label for="umConfirmPassword">Confirm new password</label>
                    <input class="um-control" id="umConfirmPassword" type="password" name="password_confirmation" autocomplete="new-password" required>
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
@endpush
