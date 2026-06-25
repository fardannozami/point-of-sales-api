<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TransactionService $transactionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $transactions = $this->transactionService->getTransactionList(
            $request->query('per_page', 15)
        );

        $resource = TransactionResource::collection($transactions)->response()->getData(true);

        return $this->successResponse($resource);
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->checkout($request->validated());

        return $this->successResponse(
            new TransactionResource($transaction),
            'Checkout successful',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $transaction = $this->transactionService->getTransactionDetail($id);

        return $this->successResponse(new TransactionResource($transaction));
    }
}
