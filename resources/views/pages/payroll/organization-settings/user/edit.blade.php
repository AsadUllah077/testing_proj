@extends('layouts.auth')

@section('title', 'Edit Payroll User')

@section('content')
    @include('pages.account.organization-settings.user.form', [
        'title' => 'Edit '.$user->full_name,
        'action' => route('payroll.organization-settings.users.update', $user),
        'submitLabel' => 'Save changes',
        'usersIndexRoute' => 'payroll.organization-settings.users.index',
        'rolesCreateRoute' => 'payroll.organization-settings.roles.create',
        'rolesEditRoute' => 'payroll.organization-settings.roles.edit',
        'rolesEditPermission' => 'payroll.organization-settings.roles.edit',
        'permissionLabel' => 'payroll',
    ])
@endsection
