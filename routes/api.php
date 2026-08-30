<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ContentImageController;
use App\Http\Controllers\Api\Admin\CouponController;
use App\Http\Controllers\Api\Admin\PaymentMethodController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Catalog\CategoryController as CatalogCategoryController;
use App\Http\Controllers\Api\Catalog\FlashSaleController;
use App\Http\Controllers\Api\Catalog\ProductController as CatalogProductController;
use App\Http\Controllers\Api\Catalog\ReviewController as CatalogReviewController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Customer\ProfileController;
use App\Http\Controllers\Api\Inventory\DamageController;
use App\Http\Controllers\Api\Inventory\PurchaseController;
use App\Http\Controllers\Api\Inventory\StockAdjustmentController;
use App\Http\Controllers\Api\Inventory\StockController;
use App\Http\Controllers\Api\Inventory\StockReturnController;
use App\Http\Controllers\Api\Inventory\StockTransferController;
use App\Http\Controllers\Api\Inventory\SupplierController;
use App\Http\Controllers\Api\Inventory\WarehouseController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Orders\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Pos\PosController;
use App\Http\Controllers\Api\Reports\MovementReportController;
use App\Http\Controllers\Api\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['message' => 'pong', 'time' => now()->toIso8601String()]));

Route::prefix('v1')->group(function () {

    // ── Auth ────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    // ── Public storefront catalog ──────────────────────────────────────
    Route::prefix('catalog')->group(function () {
        Route::get('products', [CatalogProductController::class, 'index']);
        Route::get('products/{slug}', [CatalogProductController::class, 'show']);
        Route::get('categories', [CatalogCategoryController::class, 'index']);
        Route::get('products/{product}/reviews', [CatalogReviewController::class, 'index']);
        Route::get('flash-sale', [FlashSaleController::class, 'index']);
    });

    // ── Guest/customer cart (works for guests via session, and users) ──
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::post('items', [CartController::class, 'addItem']);
        Route::put('items/{item}', [CartController::class, 'updateItem']);
        Route::delete('items/{item}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])
        ->middleware('auth:sanctum')
        ->name('invoices.download');

    // ── Authenticated routes ────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Product reviews (any logged-in user can review any product)
        Route::post('catalog/products/{product}/reviews', [CatalogReviewController::class, 'store']);
        Route::get('catalog/products/{product}/reviews/mine', [CatalogReviewController::class, 'mine']);

        // Customer self-service
        Route::prefix('customer')->group(function () {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::put('profile', [ProfileController::class, 'update']);

            Route::apiResource('addresses', AddressController::class)->except(['show']);

            Route::get('orders', [CustomerOrderController::class, 'index']);
            Route::post('checkout', [CustomerOrderController::class, 'checkout']);
            Route::get('orders/{order}', [CustomerOrderController::class, 'show']);
            Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);
        });

        // ── Admin: catalog & people management ─────────────────────────
        Route::prefix('admin')->group(function () {
            Route::apiResource('products', AdminProductController::class);
            Route::post('content-images', [ContentImageController::class, 'store']);
            Route::get('products/{product}/barcode', [AdminProductController::class, 'barcode']);
            Route::delete('products/{product}/images/{image}', [AdminProductController::class, 'destroyImage']);
            Route::delete('products/{product}/variants/{variant}', [AdminProductController::class, 'destroyVariant']);

            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('brands', AdminBrandController::class);
            Route::apiResource('coupons', CouponController::class);

            Route::apiResource('users', UserController::class);
            Route::get('roles', [RoleController::class, 'index']);
            Route::post('roles', [RoleController::class, 'store']);
            Route::put('roles/{role}', [RoleController::class, 'update']);
            Route::get('permissions', [RoleController::class, 'permissions']);

            Route::apiResource('payment-methods', PaymentMethodController::class)->only(['index', 'store', 'update']);

            Route::get('activity-logs', [ActivityLogController::class, 'index']);

            // Review moderation
            Route::get('reviews', [AdminReviewController::class, 'index']);
            Route::put('reviews/{review}/approve', [AdminReviewController::class, 'approve']);
            Route::put('reviews/{review}/reject', [AdminReviewController::class, 'reject']);
            Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);

            // Order management across all channels
            Route::get('orders', [AdminOrderController::class, 'index']);
            Route::post('orders/facebook', [AdminOrderController::class, 'storeFacebookOrder']);
            Route::get('orders/{order}', [AdminOrderController::class, 'show']);
            Route::put('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
            Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel']);
            Route::post('orders/{order}/payments', [AdminOrderController::class, 'recordPayment']);
            Route::post('orders/{order}/invoice', [AdminOrderController::class, 'generateInvoice']);
        });

        // ── Point of sale (outlet) ──────────────────────────────────────
        Route::prefix('pos')->group(function () {
            Route::post('checkout', [PosController::class, 'checkout']);
            Route::get('lookup/{barcode}', [PosController::class, 'lookup']);
        });

        // ── Inventory: warehouses, stock, purchasing, transfers ─────────
        Route::prefix('inventory')->group(function () {
            Route::apiResource('warehouses', WarehouseController::class);
            Route::apiResource('suppliers', SupplierController::class);

            Route::get('stock', [StockController::class, 'index']);
            Route::get('stock/low-stock', [StockController::class, 'lowStock']);
            Route::get('stock/movements', [StockController::class, 'movements']);

            Route::apiResource('purchases', PurchaseController::class)->only(['index', 'store', 'show']);
            Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
            Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);

            Route::apiResource('stock-transfers', StockTransferController::class)->only(['index', 'store', 'show']);
            Route::post('stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive']);

            Route::apiResource('stock-adjustments', StockAdjustmentController::class)->only(['index', 'store', 'show']);
            Route::apiResource('stock-returns', StockReturnController::class)->only(['index', 'store', 'show']);
            Route::apiResource('damages', DamageController::class)->only(['index', 'store', 'show']);
        });

        // ── Reports ──────────────────────────────────────────────────────
        Route::prefix('reports')->group(function () {
            Route::get('sales', [ReportController::class, 'sales']);
            Route::get('orders', [ReportController::class, 'orders']);
            Route::get('products', [ReportController::class, 'products']);
            Route::get('stock', [ReportController::class, 'stock']);
            Route::get('movement', [MovementReportController::class, 'index']);
            Route::get('movement/export', [MovementReportController::class, 'export']);
            Route::get('movement/thresholds', [MovementReportController::class, 'thresholds']);
            Route::put('movement/thresholds', [MovementReportController::class, 'updateThresholds']);
        });
    });
});
