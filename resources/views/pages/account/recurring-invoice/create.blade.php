@extends('layouts.auth')
@section('title') New Recurring Invoice @endsection

@section('content')
@php $editing = false; $ri = null; @endphp
@include('pages.account.recurring-invoice._form')
@endsection
