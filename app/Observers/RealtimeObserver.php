<?php

namespace App\Observers;

use App\Events\ExpenseCreated;
use App\Events\ExpenseDeleted;
use App\Events\ExpenseUpdated;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Events\MenuUpdated;
use App\Models\DailyItem;
use App\Models\Expense;
use App\Models\Item;
use Illuminate\Database\Eloquent\Model;

/**
 * Tells the open screens when menu items, items and expenses change, whichever way they were
 * changed (the web screens or the API). Registered for all three models in AppServiceProvider.
 *
 * Transactions are not here: one order touches several tables, so TransactionService
 * and the API's update announce them once, when the whole change is saved.
 */
class RealtimeObserver
{
    public function created(Model $model): void
    {
        match (true) {
            $model instanceof DailyItem => MenuUpdated::fire($model->id, 'added'),
            $model instanceof Item => ItemCreated::fire($model->id),
            $model instanceof Expense => ExpenseCreated::fire($model->id),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        match (true) {
            $model instanceof DailyItem => MenuUpdated::fire($model->id, $model->wasChanged('status') ? 'status' : 'updated'),
            $model instanceof Item => ItemUpdated::fire($model->id),
            $model instanceof Expense => ExpenseUpdated::fire($model->id),
            default => null,
        };
    }

    public function deleted(Model $model): void
    {
        match (true) {
            $model instanceof DailyItem => MenuUpdated::fire($model->id, 'removed'),
            $model instanceof Item => ItemDeleted::fire($model->id),
            $model instanceof Expense => ExpenseDeleted::fire($model->id),
            default => null,
        };
    }
}
