@php
    $topbar = [
        'title' => 'Access Denied',
        'description' => 'You do not have permission to open this page.',
    ];
@endphp

@extends('layouts.app')

@section('title', 'Access denied')

@section('content')
<div class="denied">
    <div class="denied-icon">
        <i data-lucide="shield-alert" aria-hidden="true"></i>
    </div>
    <h3>You do not have access to this page</h3>
    <p>If you need it, please ask the administrator to give your account access.</p>
    <a class="denied-link" href="{{ route('departments.index') }}">Go to my dashboard</a>
</div>
@endsection

@push('styles')
<style>
    .denied {
        max-width: 480px;
        margin: 30px auto;
        padding: 45px 25px;
        text-align: center;
        background: var(--white);
        border: 1px dashed var(--border);
        border-radius: 14px;
    }

    .denied-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 65px;
        height: 65px;
        margin: 0 auto 18px;
        color: #fff;
        background: #28754F;
        border-radius: 18px;
    }

    .denied-icon svg {
        width: 30px;
        height: 30px;
    }

    .denied h3 {
        margin: 0 0 9px;
        color: var(--primary-dark);
        font-size: 16px;
    }

    .denied p {
        margin: 0 0 20px;
        color: var(--muted);
        font-size: 12px;
        line-height: 1.7;
    }

    .denied-link {
        display: inline-flex;
        align-items: center;
        min-height: 40px;
        padding: 0 18px;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        background: var(--primary);
        border-radius: 9px;
    }

    .denied-link:hover {
        background: var(--primary-dark);
    }
</style>
@endpush
