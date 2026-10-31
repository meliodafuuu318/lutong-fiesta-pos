<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Exceptions\TransactionException;
use App\Http\Requests\Transaction\WebAddPaymentRequest;
use App\Http\Requests\Transaction\WebStoreTransactionRequest;
use App\Models\Category;
use App\Models\DailyItem;
use App\Models\Transaction;
use App\Services\MenuService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $query = Transaction::with('delivery')
            ->withSum('transactionPayments', 'payment_amount');

        if ($request->filled('search')) {
            $query->where('reference_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['transaction_date', 'total', 'reference_number', 'status', 'payment_status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderByDesc('transaction_date')->orderByDesc('id');
        }

        if (! in_array($sortBy, $allowedSorts, true) || $sortBy === 'transaction_date') {
            $query->orderByDesc('id');
        }

        $transactions = $query->paginate($request->input('per_page', self::PER_PAGE))->withQueryString();

        $search = $request->input('search');
        $type = $request->input('type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        return view('transactions.index', compact('transactions', 'search', 'type', 'dateFrom', 'dateTo', 'sortBy', 'sortOrder'));
    }

    public function create(MenuService $menu): View
    {
        ['categories' => $categories, 'menuByCategory' => $menuByCategory] = $menu->today();

        return view('transactions.create', compact('categories', 'menuByCategory'));
    }

    public function store(WebStoreTransactionRequest $request, TransactionService $transactions): RedirectResponse
    {
        try {
            $transaction = $transactions->create($request->validated());
        } catch (TransactionException $e) {
            // An item stopped being available between opening the form and submitting it.
            $name = $e->dailyItemId ? DailyItem::with('item')->find($e->dailyItemId)?->item?->name : null;

            return back()->withInput()->withErrors([
                'transaction' => $name
                    ? "{$name} isn't available today. Remove it and try again."
                    : $e->getMessage(),
            ], 'createTransaction');
        }

        return redirect()->route('transactions.show', $transaction)
            ->with('status', "Transaction {$transaction->reference_number} created.");
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load([
            'transactionItems.dailyItem.item',
            'transactionPayments',
            'delivery',
        ]);

        $transaction->loadSum('transactionPayments', 'payment_amount');

        $payments = $transaction->transactionPayments->sortByDesc('id');

        return view('transactions.show', compact('transaction', 'payments'));
    }

    public function addPayment(WebAddPaymentRequest $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->ensureRiderHandlesDeliveriesOnly($transaction);

        try {
            $transactions->addPayment(
                $transaction,
                $request->validated('payment_method'),
                $request->validated('payment_amount'),
            );
        } catch (TransactionException $e) {
            return $this->backToPendingOrders()->withErrors(['order' => $e->getMessage()]);
        }

        $transaction->refresh();
        $amount = number_format((float) $request->validated('payment_amount'), 2);

        if ($transaction->payment_status === 'PAID') {
            $message = "Payment of ₱{$amount} recorded. {$transaction->reference_number} is fully paid.";

            if ($transaction->change() > 0) {
                $message .= ' Change: ₱' . number_format($transaction->change(), 2) . '.';
            }
        } else {
            $message = "Payment of ₱{$amount} recorded. ₱" . number_format($transaction->outstandingBalance(), 2) . ' still due.';
        }

        return $this->backToPendingOrders()->with('status', $message);
    }

    public function markDelivered(Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $this->ensureRiderHandlesDeliveriesOnly($transaction);

        try {
            $transactions->markDelivered($transaction);
        } catch (TransactionException $e) {
            return $this->backToPendingOrders()->withErrors(['order' => $e->getMessage()]);
        }

        return $this->backToPendingOrders()
            ->with('status', "{$transaction->reference_number} marked as delivered.");
    }

    public function cancel(Request $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        try {
            $transactions->cancel($transaction);
        } catch (TransactionException $e) {
            return redirect()->route('transactions.show', $transaction)
                ->withErrors(['transaction' => $e->getMessage()]);
        }

        return redirect()->to($this->safeReturnUrl($request->input('return_to')))
            ->with('status', "{$transaction->reference_number} cancelled.");
    }

    public function refund(Request $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        try {
            $transactions->refund($transaction);
        } catch (TransactionException $e) {
            return redirect()->route('transactions.show', $transaction)
                ->withErrors(['transaction' => $e->getMessage()]);
        }

        return redirect()->to($this->safeReturnUrl($request->input('return_to')))
            ->with('status', "{$transaction->reference_number} returned/refunded.");
    }

    private function safeReturnUrl(mixed $returnTo): string
    {
        if (is_string($returnTo) && parse_url($returnTo, PHP_URL_HOST) === request()->getHost()) {
            return $returnTo;
        }

        return route('transactions.index');
    }

    /** Both actions happen on the pending orders list: the dashboard's tab, or the cashier's own page. */
    private function backToPendingOrders(): RedirectResponse
    {
        if (auth()->user()->isCashier()) {
            return redirect()->route('pending-payments');
        }

        return redirect()->to(route('dashboard') . '#tab-pending');
    }

    /** Walk in orders aren't a rider's business. */
    private function ensureRiderHandlesDeliveriesOnly(Transaction $transaction): void
    {
        abort_if(auth()->user()->isRider() && $transaction->type !== 'DELIVERY', 403, "You don't have access to this transaction.");
    }
}
