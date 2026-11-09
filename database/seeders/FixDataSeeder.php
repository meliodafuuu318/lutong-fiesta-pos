<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\DailyItem;
use App\Models\Category;
use App\Models\Item;
use App\Models\Delivery;

class FixDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $duplicate = Transaction::where('status', 'REFUNDED')->delete();

        // // Remove delivery fee item and add delivery fee to the delivery table
        // $item = Item::where('name', 'Delivery Fee')->first();
        // $dailyItems = DailyItem::where('item_id', $item->id)->get();
        // $transactionItems = TransactionItem::whereIn('daily_item_id', $dailyItems->pluck('id'))->get();

        // $transactionIds = $transactionItems->pluck('transaction_id')->unique();
        // $transactions = Transaction::whereIn('id', $transactionIds)->get();

        // $transactionItems->each(function ($transactionItem) {
        //     $transactionItem->delete();
        // });
        // $dailyItems->each(function ($dailyItem) {
        //     $dailyItem->delete();
        // });
        // $item->delete();
        
        // foreach ($transactions as $transaction) {
        //     $transaction->delivery()->update([
        //         'delivery_fee' => 25.00,
        //     ]);
        // }

        // // Delete incorrect daily item
        // $incorrectDailyItem = DailyItem::whereHas('item', function ($query) {
        //     $query->where('name', 'Ginataang Langka');
        // })->whereDate('created_at', '2026-11-08')->first();
        // if ($incorrectDailyItem) {
        //     $incorrectDailyItem->delete();
        // }
    }
}
