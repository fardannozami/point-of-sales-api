<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getList(int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::with('items')->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?Transaction
    {
        return Transaction::with('items')->find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Transaction
    {
        $transaction = Transaction::create([
            'transaction_number' => $data['transaction_number'],
            'total_amount' => $data['total_amount'],
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_price' => $item['product_price'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['subtotal'],
                ]);
            }
        }

        return $transaction->load('items');
    }
}
