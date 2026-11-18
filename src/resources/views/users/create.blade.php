@extends('layouts.app')

@section('title', 'Add user')

@section('content')
    @include('users.partials.form', ['action' => route('users.store'), 'submitLabel' => 'Add user'])
@endsection

@push('styles')
    @include('users.partials.styles')
@endpush

@push('scripts')
    @include('users.partials.form-script')
@endpush
