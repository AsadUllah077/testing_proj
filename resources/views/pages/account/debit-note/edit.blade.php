@extends('layouts.auth')
@section('title') Edit Debit Note @endsection

@section('content')
@php $editing = true; $dn = $debitNote; @endphp
@include('pages.account.debit-note._form')
@endsection
