<?php

namespace App\Providers;

use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\Eloquent\InventoryRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\StockMovementRepository;
use App\Repositories\Eloquent\StockTransferRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Repositoryのインターフェースと、実際に使うクラスの対応を登録する。
 * UseCaseは、インターフェースを引数で受け取る（Laravelが対応するクラスを渡す）。
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        InventoryRepositoryInterface::class => InventoryRepository::class,
        StockMovementRepositoryInterface::class => StockMovementRepository::class,
        StockTransferRepositoryInterface::class => StockTransferRepository::class,
        ProductRepositoryInterface::class => ProductRepository::class,
        WarehouseRepositoryInterface::class => WarehouseRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
    ];
}
