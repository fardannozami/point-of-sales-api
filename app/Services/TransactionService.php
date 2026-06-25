<?php

namespace App\Services;

use App\Repositories\TransactionRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use App\Models\Transaction;
use App\Models\Product;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository,
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get a paginated list of transactions.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactionList(int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->getList($perPage);
    }

    /**
     * Get transaction detail by ID.
     *
     * @param int $id
     * @return Transaction
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getTransactionDetail(int $id): Transaction
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Transaction::class, [$id]);
        }

        return $transaction;
    }

    /**
     * Checkout a transaction.
     *
     * @param array $data
     * @return Transaction
     * @throws ValidationException
     * @throws InsufficientStockException
     */
    public function checkout(array $data): Transaction
    {
        // 1. Validate request payload structure
        $validator = Validator::make($data, [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $validator->validate();

        return DB::transaction(function () use ($data) {
            $itemsData = [];
            $totalAmount = 0.00;

            // Group duplicate product IDs in items array to lock correctly and prevent deadlocks
            $groupedItems = [];
            foreach ($data['items'] as $item) {
                $pid = $item['product_id'];
                $qty = $item['qty'];
                if (isset($groupedItems[$pid])) {
                    $groupedItems[$pid] += $qty;
                } else {
                    $groupedItems[$pid] = $qty;
                }
            }

            // Sort keys to prevent deadlock (standard practice in database transaction locks)
            ksort($groupedItems);

            $insufficientStockErrors = [];

            foreach ($groupedItems as $productId => $qty) {
                // 2. Fetch product with row-level lock (SELECT ... FOR UPDATE)
                $product = $this->productRepository->findByIdForUpdate($productId);

                if (!$product) {
                    throw ValidationException::withMessages([
                        "items" => ["Product with ID {$productId} not found."]
                    ]);
                }

                // 3. Validate stock availability
                if ($product->stock < $qty) {
                    $insufficientStockErrors[$productId] = [
                        "Available stock is {$product->stock}"
                    ];
                    continue;
                }

                $subtotal = $product->price * $qty;
                $totalAmount += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_price' => $product->price,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'product_model' => $product, // store ref for stock deduction
                ];
            }

            // If there are any stock validation errors, abort and throw custom exception
            if (!empty($insufficientStockErrors)) {
                throw new InsufficientStockException("Insufficient stock", $insufficientStockErrors);
            }

            // 4. Generate transaction number (format: TRX-YYYYMMDD-NNNNN)
            $today = now()->format('Ymd');
            $count = Transaction::whereDate('created_at', now())->count();
            $sequence = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
            $transactionNumber = "TRX-{$today}-{$sequence}";

            // 5. Save transaction header and items via repository
            $transaction = $this->transactionRepository->create([
                'transaction_number' => $transactionNumber,
                'total_amount' => $totalAmount,
                'items' => $itemsData,
            ]);

            // 6. Deduct stock atomically
            foreach ($itemsData as $item) {
                $product = $item['product_model'];
                $newStock = $product->stock - $item['qty'];
                $this->productRepository->update($product->id, ['stock' => $newStock]);
            }

            return $transaction;
        });
    }
}
