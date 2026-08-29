@extends('layouts.auth')

@section('title', 'Create Accounting User')

@section('content')
    @include('pages.account.organization-settings.user.form', [
        'title' => 'Create Accounting User',
        'action' => route('account.organization-settings.users.store'),
        'submitLabel' => 'Create user',
        'user' => null,
    ])
@endsection
