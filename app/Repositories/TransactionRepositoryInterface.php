<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TransactionRepositoryInterface
{
    /**
     * Get a paginated list of transactions, eager-loading transaction items.
     *
     * @param int $perPage
     * @return LengthAwarePaginator<Transaction>
     */
    public function getList(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a transaction by ID with eager-loaded transaction items.
     *
     * @param int $id
     * @return Transaction|null
     */
    public function findById(int $id): ?Transaction;

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
     * @return Transaction
     */
    public function create(array $data): Transaction;
}
