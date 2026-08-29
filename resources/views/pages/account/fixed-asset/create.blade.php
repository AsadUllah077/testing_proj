@extends('layouts.auth')
@section('title') Add Fixed Asset @endsection
@section('content')
@php $editing = false; $asset = null; @endphp
@include('pages.account.fixed-asset._form')
@endsection
