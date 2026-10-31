@extends('layouts.main')

@section('title', 'Expenses · ' . config('app.name'))

@section('main')
@php
    // On phones each row becomes a card: the description is its heading, the rest are label/value rows.
    $labelRow = 'max-md:col-span-2 max-md:flex max-md:items-center max-md:justify-between max-md:gap-3 max-md:border-t max-md:border-cream-200 max-md:px-0 max-md:py-2 max-md:before:text-xs max-md:before:font-bold max-md:before:uppercase max-md:before:tracking-wide max-md:before:text-ink-soft max-md:before:content-[attr(data-label)]';
    $filtered = filled($dateFrom) || filled($dateTo);
    // Negative amounts are money coming in: shown with a minus sign, in teal instead of red
    $signedPeso = fn ($amount) => ((float) $amount < 0 ? '−' : '') . '₱' . number_format(abs((float) $amount), 2);
@endphp

<div class="w-full">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl font-bold text-teal-900 sm:text-3xl">Expenses</h1>
        <button
            type="button"
            data-expense-add-open
            class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-display text-base font-bold leading-tight text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)]"
        >Add Expense</button>
    </div>

    <form method="GET" action="{{ route('expenses.index') }}" class="mt-6 rounded-xl border-2 border-cream-200 bg-cream-50 p-4">
        <div class="grid grid-cols-1 gap-3 min-[26rem]:grid-cols-2 lg:max-w-xl">
            <div>
                <label for="expenses-date-from" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Date From</label>
                <input
                    id="expenses-date-from"
                    name="date_from"
                    type="date"
                    value="{{ $dateFrom ?? '' }}"
                    class="mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500"
                >
            </div>
            <div>
                <label for="expenses-date-to" class="block text-xs font-bold uppercase tracking-wide text-ink-soft">Date To</label>
                <input
                    id="expenses-date-to"
                    name="date_to"
                    type="date"
                    value="{{ $dateTo ?? '' }}"
                    class="mt-1 w-full rounded-lg border-2 border-cream-200 bg-white px-3 py-2 text-sm font-semibold text-ink focus:border-teal-500"
                >
            </div>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 sm:flex sm:items-center sm:justify-end">
            <a href="{{ route('expenses.index') }}" class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 text-center text-sm font-bold hover:border-teal-500 sm:py-2">Reset</a>
            <button type="submit" class="cursor-pointer rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-bold text-cream-50 hover:bg-teal-600 sm:py-2">Apply</button>
        </div>
    </form>

    @if ($expenses->isEmpty())
        <p class="mt-6 rounded-xl border-2 border-dashed border-cream-200 px-6 py-12 text-center text-ink-soft">
            {{ $filtered ? 'No expenses in this date range.' : 'No expenses yet.' }}
        </p>
    @else
        <div class="mt-6 flex flex-wrap items-baseline justify-between gap-x-4">
            <p class="text-sm font-semibold text-ink-soft">{{ $filtered ? 'Net total for this date range' : 'Net total, all time' }}</p>
            <p @class(['font-display text-xl font-bold', 'text-danger' => (float) $total >= 0, 'text-teal-700' => (float) $total < 0])>{{ $signedPeso($total) }}</p>
        </div>
        <p class="text-xs font-semibold text-ink-soft">Money that came in (shown as negative amounts) is subtracted from what was spent.</p>

        <div class="mt-2 rounded-xl border-2 border-cream-200 bg-cream-50 max-md:border-0 max-md:bg-transparent">
            <div class="overflow-x-auto">
                <table class="w-full text-center max-md:block md:min-w-[48rem] md:table-fixed">
                    <caption class="sr-only">Expenses</caption>
                    {{-- Six equal parts: date 1, description 2, amount 1, edit 1, delete 1 --}}
                    <colgroup class="max-md:hidden">
                        <col class="w-1/6">
                        <col class="w-2/6">
                        <col class="w-1/6">
                        <col class="w-1/6">
                        <col class="w-1/6">
                    </colgroup>
                    <thead class="bg-cream-100 text-xs font-bold uppercase tracking-wide text-ink-soft max-md:hidden sm:text-sm">
                        <tr>
                            <th scope="col" class="px-3 py-3 lg:px-6">Date</th>
                            <th scope="col" class="px-3 py-3 lg:px-6">Description</th>
                            <th scope="col" class="px-3 py-3 lg:px-6">Amount</th>
                            <th scope="col" class="px-3 py-3 lg:px-6"><span class="sr-only">Edit</span></th>
                            <th scope="col" class="px-3 py-3 lg:px-6"><span class="sr-only">Delete</span></th>
                        </tr>
                    </thead>
                    <tbody class="max-md:block max-md:space-y-3">
                        @foreach ($expenses as $expense)
                            <tr class="border-t-2 border-cream-200 hover:bg-cream-100/60 max-md:grid max-md:grid-cols-2 max-md:gap-x-2 max-md:rounded-xl max-md:border-2 max-md:bg-cream-50 max-md:p-4">
                                <th scope="row" data-label="Date" class="px-3 py-3 font-semibold text-ink lg:px-6 {{ $labelRow }}">{{ $expense->expense_date->format('M j, Y') }}</th>
                                <td class="break-words px-3 py-3 font-semibold text-ink lg:px-6 max-md:order-first max-md:col-span-2 max-md:block max-md:p-0 max-md:pb-2 max-md:text-left max-md:font-display max-md:text-lg max-md:font-bold">{{ $expense->description }}</td>
                                <td data-label="Amount" @class(['break-words px-3 py-3 font-bold lg:px-6', 'text-danger' => (float) $expense->amount > 0, 'text-teal-700' => (float) $expense->amount < 0, $labelRow])>
                                    {{ $signedPeso($expense->amount) }}
                                    @if ((float) $expense->amount < 0)
                                        <span class="ml-1 rounded-full bg-teal-100 px-2 py-0.5 align-middle text-xs font-bold text-teal-900">Money in</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 lg:px-6 max-md:border-t max-md:border-cream-200 max-md:px-0 max-md:pb-0 max-md:pt-3">
                                    <button
                                        type="button"
                                        data-expense-edit
                                        data-id="{{ $expense->id }}"
                                        data-url="{{ route('expenses.update', $expense) }}"
                                        data-amount="{{ $expense->amount }}"
                                        data-description="{{ $expense->description }}"
                                        data-date="{{ $expense->expense_date->format('M j, Y') }}"
                                        aria-label="Edit expense: {{ $expense->description }}"
                                        class="inline-flex size-10 cursor-pointer items-center justify-center gap-2 rounded-lg text-ink-soft hover:bg-teal-100 hover:text-teal-700 max-md:h-11 max-md:w-full max-md:border-2 max-md:border-cream-200 max-md:bg-white max-md:font-bold max-md:text-ink"
                                    >
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                        <span class="md:hidden" aria-hidden="true">Edit</span>
                                    </button>
                                </td>
                                <td class="px-3 py-3 lg:px-6 max-md:border-t max-md:border-cream-200 max-md:px-0 max-md:pb-0 max-md:pt-3">
                                    <button
                                        type="button"
                                        data-expense-delete
                                        data-url="{{ route('expenses.destroy', $expense) }}"
                                        data-amount="{{ $signedPeso($expense->amount) }}"
                                        data-description="{{ $expense->description }}"
                                        data-date="{{ $expense->expense_date->format('M j, Y') }}"
                                        aria-label="Delete expense: {{ $expense->description }}"
                                        class="inline-flex size-10 cursor-pointer items-center justify-center gap-2 rounded-lg text-ink-soft hover:bg-danger/10 hover:text-danger max-md:h-11 max-md:w-full max-md:border-2 max-md:border-danger/40 max-md:bg-white max-md:font-bold max-md:text-danger"
                                    >
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                            <path d="M10 11v6M14 11v6"/>
                                        </svg>
                                        <span class="md:hidden" aria-hidden="true">Delete</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $expenses->links('pagination.lutong') }}
        </div>
    @endif
</div>

{{-- Delete confirmation --}}
<dialog
    id="delete-expense-dialog"
    aria-labelledby="delete-expense-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <form method="POST" data-delete-expense-form class="p-5">
        @csrf
        @method('DELETE')
        <h2 id="delete-expense-title" class="font-display text-2xl font-bold leading-tight text-teal-900">Delete Expense?</h2>
        <p class="mt-2 text-ink-soft">
            <strong class="text-ink" data-delete-expense-amount></strong> for
            <strong class="break-words text-ink" data-delete-expense-description></strong>
            (<span data-delete-expense-date></span>) will be permanently deleted. This can't be undone.
        </p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" class="cursor-pointer rounded-lg bg-danger px-4 py-2.5 font-bold text-cream-50 hover:brightness-110 disabled:cursor-wait disabled:opacity-60">Delete</button>
        </div>
    </form>
</dialog>

{{-- Add / edit expense modal (one modal, two modes) --}}
<dialog
    id="expense-dialog"
    aria-labelledby="expense-dialog-title"
    data-reopen="{{ $errors->expenseForm->any() ? '1' : '' }}"
    data-old-expense-id="{{ old('expense_id') }}"
    data-old-amount="{{ old('amount') }}"
    data-old-description="{{ old('description') }}"
    data-today="{{ now()->format('M j, Y') }}"
    class="m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="flex items-start justify-between gap-4 border-b-2 border-cream-200 px-5 py-4">
        <div>
            <h2 id="expense-dialog-title" data-form-title class="font-display text-2xl font-bold leading-tight text-teal-900">Add Expense</h2>
            <p data-form-subtitle class="mt-0.5 text-sm font-semibold text-ink-soft">Recorded for today, {{ now()->format('M j, Y') }}</p>
        </div>
        <button type="button" data-dialog-close aria-label="Close" class="-mr-2 flex size-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-ink hover:bg-cream-100">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <form
        method="POST"
        action="{{ route('expenses.store') }}"
        data-expense-form
        data-store-url="{{ route('expenses.store') }}"
        class="px-5 py-5"
        novalidate
    >
        @csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        <input type="hidden" name="expense_id" value="">

        <div class="space-y-5">
            <div>
                <label for="expense-amount" class="block text-sm font-bold">Amount</label>
                <div class="relative mt-1.5">
                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-lg font-bold text-ink-soft">₱</span>
                    <input
                        id="expense-amount" name="amount" type="number" inputmode="decimal" step="0.01" required
                        class="w-full rounded-lg border-2 bg-white py-3 pl-10 pr-4 text-lg font-bold text-ink @error('amount', 'expenseForm') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                    >
                </div>
                @error('amount', 'expenseForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-sm text-ink-soft">Use a negative amount for money coming in, like change from money you took out earlier or income from outside the orders.</p>
            </div>

            <div>
                <label for="expense-description" class="block text-sm font-bold">Description</label>
                <input
                    id="expense-description" name="description" type="text" maxlength="255" required autocomplete="off"
                    placeholder="What was it for?"
                    class="mt-1.5 w-full rounded-lg border-2 bg-white px-4 py-3 text-base font-semibold text-ink @error('description', 'expenseForm') border-danger @else border-cream-200 focus:border-teal-500 @enderror"
                >
                @error('description', 'expenseForm')
                    <p class="mt-1.5 text-sm font-semibold text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-3 font-bold hover:border-teal-500">Cancel</button>
            <button type="submit" data-form-submit class="cursor-pointer rounded-lg bg-amber-500 px-4 py-3 font-display text-lg font-bold leading-snug text-ink shadow-[0_4px_0_0_var(--color-amber-600)] transition hover:bg-amber-400 active:translate-y-[2px] active:shadow-[0_2px_0_0_var(--color-amber-600)] disabled:cursor-wait disabled:opacity-60 disabled:shadow-none">Save Expense</button>
        </div>
    </form>
</dialog>

{{-- Add / edit confirmation --}}
<dialog
    id="expense-confirm-dialog"
    aria-labelledby="expense-confirm-title"
    class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl border-2 border-cream-200 bg-cream-50 p-0 text-ink shadow-2xl backdrop:bg-ink/50"
>
    <div class="p-5">
        <h2 id="expense-confirm-title" data-confirm-title class="font-display text-2xl font-bold leading-tight text-teal-900">Save Expense?</h2>
        <p class="mt-2 text-ink-soft">
            <span data-confirm-verb>Record</span>
            <strong class="text-ink" data-confirm-amount></strong> for
            <strong class="break-words text-ink" data-confirm-description></strong>?
        </p>
        <p data-confirm-money-in hidden class="mt-2 text-sm font-semibold text-teal-700">This is money coming in, so it will be subtracted from your expenses.</p>
        <div class="mt-5 grid grid-cols-2 gap-2">
            <button type="button" data-dialog-close class="cursor-pointer rounded-lg border-2 border-cream-200 bg-white px-4 py-2.5 font-bold hover:border-teal-500">Cancel</button>
            <button type="button" data-expense-confirm class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-ink hover:bg-amber-400 disabled:cursor-wait disabled:opacity-60">Confirm</button>
        </div>
    </div>
</dialog>
@endsection
