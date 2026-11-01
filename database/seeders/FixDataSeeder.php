<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\DailyItem;
use App\Models\Category;
use App\Models\Item;

class FixDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $duplicate = Transaction::where('status', 'REFUNDED')->delete();
        // $updateStatus = Transaction::where('payment_status', 'PENDING')
        //     ->where('status', 'PENDING')
        //     ->update([
        //         'status' => 'COMPLETED',
        //         'payment_status' => 'PAID',
        //     ]);

        // $cancelledTransactions = Transaction::where('status', 'CANCELLED')->delete();

        // $wrongTransactionDailyItem = TransactionItem::find(13);
        // $wrongTransactionDailyItem->update([
        //     'daily_item_id' => 17,
        // ]);

        // $wrongDailyItem = DailyItem::find(7);
        // $wrongDailyItem->delete();

        // Add new category
        // Category::create([
        //     'name' => 'Snacks'
        // ]);
        // Category::where('name', 'Food')->update([
        //     'name' => 'Meals'
        // ]);

        // Move snacks to the new category
        // $snackNames = [
        //     'Kwek-Kwek (6pcs)', 'Squid Balls (8pcs)', 'Chicken Balls (8pcs)', 'Kikiam (Big, 6pcs)', 'Fishballs (20pcs)', 'Cheese Sticks (10pcs)', 'Fried Pork Siomai (10pcs)'
        // ];

        // $snacks = Item::whereIn('name', $snackNames)->get();
        // $snacks->each(function ($snack) {
        //     $snack->category_id = Category::where('name', 'Snacks')->first()->id;
        //     $snack->save();
        // });
    }
}
