<?php

namespace App\Providers;

use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Repositories\Contracts\DamageRepositoryInterface;
use App\Repositories\Contracts\LowStockAlertRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentMethodRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Contracts\StockAdjustmentRepositoryInterface;
use App\Repositories\Contracts\StockRepositoryInterface;
use App\Repositories\Contracts\StockReturnRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\Eloquent\BrandRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\CouponRepository;
use App\Repositories\Eloquent\DamageRepository;
use App\Repositories\Eloquent\LowStockAlertRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\PaymentMethodRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\PurchaseRepository;
use App\Repositories\Eloquent\StockAdjustmentRepository;
use App\Repositories\Eloquent\StockRepository;
use App\Repositories\Eloquent\StockReturnRepository;
use App\Repositories\Eloquent\StockTransferRepository;
use App\Repositories\Eloquent\SupplierRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $repositoryBindings = [
        ProductRepositoryInterface::class => ProductRepository::class,
        CategoryRepositoryInterface::class => CategoryRepository::class,
        BrandRepositoryInterface::class => BrandRepository::class,
        SupplierRepositoryInterface::class => SupplierRepository::class,
        WarehouseRepositoryInterface::class => WarehouseRepository::class,
        StockRepositoryInterface::class => StockRepository::class,
        OrderRepositoryInterface::class => OrderRepository::class,
        CouponRepositoryInterface::class => CouponRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        PurchaseRepositoryInterface::class => PurchaseRepository::class,
        StockTransferRepositoryInterface::class => StockTransferRepository::class,
        StockAdjustmentRepositoryInterface::class => StockAdjustmentRepository::class,
        StockReturnRepositoryInterface::class => StockReturnRepository::class,
        DamageRepositoryInterface::class => DamageRepository::class,
        PaymentMethodRepositoryInterface::class => PaymentMethodRepository::class,
        LowStockAlertRepositoryInterface::class => LowStockAlertRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositoryBindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
