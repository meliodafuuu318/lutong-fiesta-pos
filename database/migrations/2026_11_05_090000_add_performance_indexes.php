<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Secondary indexes for the queries the app actually runs. On MySQL (InnoDB) every index created
 * here is a B-tree, so there is nothing extra to "turn on".
 *
 * Primary keys, foreign keys (transaction_id, daily_item_id, item_id, category_id), the unique
 * transactions.reference_number and unique deliveries.transaction_id are already indexed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Dashboard today/week/month ranges (transaction_date BETWEEN ... AND status NOT IN ...),
            // list sorting by date, and date filters.
            $table->index(['transaction_date', 'status'], 'transactions_date_status_index');
            // "Pending payment" list: PENDING is a small slice of a table that is mostly PAID.
            $table->index(['payment_status', 'transaction_date'], 'transactions_payment_status_date_index');
            // API list filters on created_at.
            $table->index('created_at', 'transactions_created_at_index');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            // "Pending delivery" list: find PENDING deliveries without scanning all of them.
            $table->index(['status', 'transaction_id'], 'deliveries_status_transaction_index');
        });

        Schema::table('daily_items', function (Blueprint $table) {
            // Today's menu, menu for a date, popularity/analytics ranges, "already on today's menu" check.
            $table->index(['date', 'item_id'], 'daily_items_date_item_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            // Today/week/month totals, list sorting and date filters.
            $table->index('expense_date', 'expenses_expense_date_index');
        });

        Schema::table('transaction_payments', function (Blueprint $table) {
            // Payments report: date filters, grouping and sorting by created_at.
            $table->index('created_at', 'transaction_payments_created_at_index');
            // SUM(payment_amount) per transaction (withSum) answered from the index alone.
            $table->index(['transaction_id', 'payment_amount'], 'transaction_payments_txn_amount_index');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropIndex('transaction_payments_txn_amount_index');
            $table->dropIndex('transaction_payments_created_at_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_expense_date_index');
        });

        Schema::table('daily_items', function (Blueprint $table) {
            $table->dropIndex('daily_items_date_item_index');
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex('deliveries_status_transaction_index');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_created_at_index');
            $table->dropIndex('transactions_payment_status_date_index');
            $table->dropIndex('transactions_date_status_index');
        });
    }
};
