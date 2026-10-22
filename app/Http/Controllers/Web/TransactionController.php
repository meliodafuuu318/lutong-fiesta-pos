<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\View\View;

class TransactionController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): View
    {
        $transactions = Transaction::with('delivery')
            ->withSum('transactionPayments', 'payment_amount')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('transactions.index', compact('transactions'));
    }
}
