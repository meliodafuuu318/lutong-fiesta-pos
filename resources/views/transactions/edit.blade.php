@extends('layouts.main')

@section('title', 'Edit Transaction · ' . config('app.name'))

@section('main')
@include('transactions.partials.order-form', [
    'editTransaction' => $transaction,
    'heading' => 'Edit Transaction',
    'subheading' => $transaction->reference_number,
    'backUrl' => route('transactions.show', ['transaction' => $transaction, 'from' => $from]),
    'emptyMenuMessage' => 'There is nothing on the menu for the day this order was taken.',
])
@endsection
