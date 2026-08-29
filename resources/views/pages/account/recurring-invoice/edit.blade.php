@extends('layouts.auth')
@section('title') Edit Recurring Invoice @endsection

@section('content')
@php $editing = true; $ri = $recurringInvoice; @endphp
@include('pages.account.recurring-invoice._form')
@endsection
