<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\ProductRepositoryInterface;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepositoryInterface;
use App\Repositories\TransactionRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
