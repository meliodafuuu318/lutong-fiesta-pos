@extends('layouts.main')

@section('title', 'Dashboard · ' . config('app.name'))

@section('main')
@include('transactions.partials.order-form')
@endsection
