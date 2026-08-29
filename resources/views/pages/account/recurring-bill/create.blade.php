@extends('layouts.auth')
@section('title') New Recurring Bill @endsection

@section('content')
@php $editing = false; $rb = null; @endphp
@include('pages.account.recurring-bill._form')
@endsection
