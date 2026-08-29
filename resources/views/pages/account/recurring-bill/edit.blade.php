@extends('layouts.auth')
@section('title') Edit Recurring Bill @endsection

@section('content')
@php $editing = true; $rb = $recurringBill; @endphp
@include('pages.account.recurring-bill._form')
@endsection
