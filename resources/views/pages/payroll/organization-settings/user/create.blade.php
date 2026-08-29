@extends('layouts.auth')

@section('title', 'Create Payroll User')

@section('content')
    @include('pages.account.organization-settings.user.form', [
        'title' => 'Create Payroll User',
        'action' => route('payroll.organization-settings.users.store'),
        'submitLabel' => 'Create user',
        'user' => null,
        'usersIndexRoute' => 'payroll.organization-settings.users.index',
        'rolesCreateRoute' => 'payroll.organization-settings.roles.create',
        'rolesEditRoute' => 'payroll.organization-settings.roles.edit',
        'rolesEditPermission' => 'payroll.organization-settings.roles.edit',
        'permissionLabel' => 'payroll',
    ])
@endsection
