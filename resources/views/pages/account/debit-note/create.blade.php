@extends('layouts.auth')
@section('title') New Debit Note @endsection

@section('content')
@php $editing = false; $dn = null; @endphp
@include('pages.account.debit-note._form')
@endsection
