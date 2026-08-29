@extends('layouts.auth')

@section('title', 'Edit Accounting User')

@section('content')
    @include('pages.account.organization-settings.user.form', [
        'title' => 'Edit '.$user->full_name,
        'action' => route('account.organization-settings.users.update', $user),
        'submitLabel' => 'Save changes',
    ])
@endsection
