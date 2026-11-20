{{-- The eye button inside a password box on the Settings page. $for is the box's id. --}}
<button type="button" class="um-password-toggle" data-show-password="{{ $for }}" aria-controls="{{ $for }}" aria-pressed="false" aria-label="Show password" title="Show password">
    <i data-lucide="eye" class="um-eye" aria-hidden="true"></i>
    <i data-lucide="eye-off" class="um-eye-off" aria-hidden="true"></i>
</button>
