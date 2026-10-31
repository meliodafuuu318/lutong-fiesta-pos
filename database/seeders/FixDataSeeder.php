<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\DailyItem;

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
    }
}
