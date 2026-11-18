@extends('layouts.app')

@section('title', 'Edit '.$user->name)

@section('content')
    @include('users.partials.form', ['action' => route('users.update', $user), 'submitLabel' => 'Save changes'])
@endsection

@push('styles')
    @include('users.partials.styles')
@endpush

@push('scripts')
    @include('users.partials.form-script')
@endpush
