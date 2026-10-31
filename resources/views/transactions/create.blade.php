@extends('layouts.main')

@section('title', 'Create Transaction · ' . config('app.name'))

@section('main')
@include('transactions.partials.order-form', [
    'heading' => 'Create Transaction',
    'backUrl' => url()->previous(route('transactions.index')),
    'emptyMenuMessage' => "There's nothing on today's menu yet. Add items to the menu from the Menu page first.",
])
@endsection
