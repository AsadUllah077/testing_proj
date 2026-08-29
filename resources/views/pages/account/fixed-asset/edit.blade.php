@extends('layouts.auth')
@section('title') Edit Fixed Asset @endsection
@section('content')
@php $editing = true; $asset = $fixedAsset; @endphp
@include('pages.account.fixed-asset._form')
@endsection
