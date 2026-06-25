<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get a paginated list of transactions, eager-loading transaction items.
     *
     * @return LengthAwarePaginator<Transaction>
     */
    public function getList(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a transaction by ID with eager-loaded transaction items.
     */
    public function findById(int $id): ?Transaction;

    /**
     * Find a transaction by transaction number with eager-loaded transaction items.
     */
    public function findByTransactionNumber(string $transactionNumber): ?Transaction;

    /**
     * Create a transaction and its items.
     *
     * @param array{
     *     transaction_number: string,
     *     total_amount: float|string,
     *     items: array<array{
     *         product_id: int,
     *         product_name: string,
     *         product_price: float|string,
     *         qty: int,
     *         subtotal: float|string
     *     }>
     * } $data
     */
    public function create(array $data): Transaction;
}
