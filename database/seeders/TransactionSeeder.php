<?php

namespace Database\Seeders;

use App\Models\DailyItem;
use App\Models\Delivery;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Traits\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    use Generator;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $transactions = [
            '2026-10-12' => [
                [
                    'type' => 'DELIVERY',
                    'address' => '63-4 San Nicolas St.',
                    'items' => [
                        [
                            'name' => 'Sinigang',
                            'quantity' => 1,
                        ],
                        [
                            'name' => 'Pork Adobo',
                            'quantity' => 1,
                        ],
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 10
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'GCASH',
                            'amount' => 260
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '12-4',
                    'items' => [
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 10
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 100
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '38-13',
                    'items' => [
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 10
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 100
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '54-14',
                    'items' => [
                        [
                            'name' => 'Chicken Pochero',
                            'quantity' => 2
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'GCASH',
                            'amount' => 160
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '30-32',
                    'items' => [
                        [
                            'name' => 'Pork Adobo',
                            'quantity' => 2
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'GCASH',
                            'amount' => 160
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '25-19',
                    'items' => [
                        [
                            'name' => 'Chicken Pochero',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Menudo',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'GCASH',
                            'amount' => 160
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Menudo',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Pork Adobo',
                            'quantity' => 1
                        ],
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 160
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '63-16',
                    'items' => [
                        [
                            'name' => 'Chicken Pochero',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Menudo',
                            'quantity' => 1
                        ],
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 160
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '57-19',
                    'items' => [
                        [
                            'name' => 'Chicken Pochero',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 80
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '23-8',
                    'items' => [
                        [
                            'name' => 'Sinigang',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Menudo',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 5
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 210
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 2
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 20
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '32-19',
                    'items' => [
                        [
                            'name' => 'Pork Adobo',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 2
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 100
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '44-17',
                    'items' => [
                        [
                            'name' => 'Menudo',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 80
                        ]
                    ]
                ],
                [
                    'type' => 'DELIVERY',
                    'address' => '38-17',
                    'items' => [
                        [
                            'name' => 'Sinigang',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 80
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Ginataang Tulingan',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Rice',
                            'quantity' => 2
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 100
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Sinigang',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Pork Adobo',
                            'quantity' => 1
                        ],
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 160
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Pork Adobo',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Rice',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 95
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Sinigang',
                            'quantity' => 1
                        ],
                        [
                            'name' => 'Ginataang Tulingan',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 150
                        ]
                    ]
                ],
                [
                    'type' => 'WALK IN',
                    'items' => [
                        [
                            'name' => 'Pork Shanghai',
                            'quantity' => 5
                        ],
                        [
                            'name' => 'Ginataang Tulingan',
                            'quantity' => 1
                        ]
                    ],
                    'payments' => [
                        [
                            'type' => 'CASH',
                            'amount' => 120
                        ]
                    ]
                ],
            ]
        ];

        foreach ($transactions as $date => $transactionsForDate) {
            foreach ($transactionsForDate as $t) {
                DB::transaction(function () use ($date, $t): void {
                    $transactionDate = Carbon::parse($date);
                    $transaction = Transaction::create([
                        'reference_number' => $this->generateReferenceNumber(),
                        'total' => 0,
                        'type' => $t['type'],
                        'status' => 'PENDING',
                        'payment_status' => 'PENDING',
                        'transaction_date' => $transactionDate,
                    ]);

                    $total = 0;

                    foreach ($t['items'] as $item) {
                        $dailyItem = DailyItem::with('item')
                            ->whereDate('date', $transactionDate)
                            ->whereHas('item', fn ($query) => $query->where('name', $item['name']))
                            ->first();

                        if (! $dailyItem) {
                            throw new \RuntimeException(
                                "Daily item not found for {$item['name']} on {$transactionDate->toDateString()}."
                            );
                        }

                        $quantity = $item['quantity'] ?? null;
                        $unitPrice = $quantity === null
                            ? (float) $item['unit_price']
                            : (float) $dailyItem->price;
                        $subtotal = $unitPrice * ($quantity ?? 1);
                        $total += $subtotal;

                        TransactionItem::create([
                            'transaction_id' => $transaction->id,
                            'daily_item_id' => $dailyItem->id,
                            'unit_price' => $unitPrice,
                            'quantity' => $quantity,
                            'subtotal' => $subtotal,
                        ]);
                    }

                    $transaction->update(['total' => $total]);

                    if ($t['type'] === 'DELIVERY') {
                        Delivery::create([
                            'transaction_id' => $transaction->id,
                            'address' => $t['address'],
                            'status' => 'DELIVERED',
                        ]);
                    }

                    $paid = 0;
                    foreach ($t['payments'] ?? [] as $paymentData) {
                        $paid += (float) $paymentData['amount'];
                        $outstanding = round($total - $paid, 2);

                        TransactionPayment::create([
                            'transaction_id' => $transaction->id,
                            'payment_method' => $paymentData['type'],
                            'payment_amount' => $paymentData['amount'],
                            'outstanding_balance' => $outstanding,
                            'total_balance' => $total,
                        ]);
                    }

                    if ($paid >= $total) {
                        $transaction->update(['payment_status' => 'PAID']);
                    }

                    $deliveryComplete = $t['type'] !== 'DELIVERY';
                    if ($paid >= $total && $deliveryComplete) {
                        $transaction->update(['status' => 'COMPLETED']);
                    }
                });
            }
        }
    }
}
