<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Expense;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = [
            '2026-10-12' => [
                'Down payment for order tomorrow' => 1500,
                'Rest of order' => 309,
                'Gas' => 100,
            ],
        ];

        foreach ($expenses as $date => $expenseItems) {
            foreach ($expenseItems as $description => $amount) {
                Expense::create([
                    'expense_date' => $date,
                    'description' => $description,
                    'amount' => $amount,
                ]);
            }
        }
    }
}
