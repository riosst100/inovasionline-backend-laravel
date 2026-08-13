<?php

use App\Http\Controllers\Api\V1\Admin\FlashSaleSlotController as AdminFlashSaleSlotController;
use App\Http\Controllers\Api\V1\Admin\SellerApplicationController as AdminSellerApplicationController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FlashSaleSlotController;
use App\Http\Controllers\Api\V1\PublicProductController;
use App\Http\Controllers\Api\V1\PublicStoreController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\V1\Seller\PromotionController as SellerPromotionController;
use App\Http\Controllers\Api\V1\SellerApplicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:6,1');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:6,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('seller-applications')->group(function () {
            Route::post('/', [SellerApplicationController::class, 'store']);
            Route::get('/me', [SellerApplicationController::class, 'show']);
        });
    });

    Route::get('/categories', [CategoryController::class, 'index']);

    Route::prefix('products')->group(function () {
        Route::get('/flash-sale', [PublicProductController::class, 'flashSale']);
        Route::get('/best-sellers', [PublicProductController::class, 'bestSellers']);
        Route::get('/{product:slug}', [PublicProductController::class, 'show']);
    });

    Route::get('/stores', [PublicStoreController::class, 'index']);

    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'index']);
        Route::get('/suggestions', [SearchController::class, 'suggestions']);
    });

    Route::prefix('regions')->group(function () {
        Route::get('/provinces', [RegionController::class, 'provinces']);
        Route::get('/provinces/{provinceCode}/cities', [RegionController::class, 'cities']);
        Route::get('/cities/{cityCode}/districts', [RegionController::class, 'districts']);
        Route::get('/districts/{districtCode}/villages', [RegionController::class, 'villages']);
    });

    Route::get('/flash-sale-slots', [FlashSaleSlotController::class, 'index']);

    Route::prefix('seller')->middleware(['auth:sanctum', 'seller', 'store.owner'])->group(function () {
        Route::prefix('products')->group(function () {
            Route::get('/', [SellerProductController::class, 'index']);
            Route::post('/', [SellerProductController::class, 'store']);
        });

        Route::prefix('promotions')->group(function () {
            Route::get('/', [SellerPromotionController::class, 'index']);
            Route::post('/', [SellerPromotionController::class, 'store']);
        });
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);

        Route::prefix('seller-applications')->group(function () {
            Route::get('/', [AdminSellerApplicationController::class, 'index']);
            Route::post('/{sellerApplication}/approve', [AdminSellerApplicationController::class, 'approve']);
            Route::post('/{sellerApplication}/reject', [AdminSellerApplicationController::class, 'reject']);
        });

        Route::prefix('flash-sale-slots')->group(function () {
            Route::get('/', [AdminFlashSaleSlotController::class, 'index']);
            Route::post('/', [AdminFlashSaleSlotController::class, 'store']);
            Route::put('/{flashSaleSlot}', [AdminFlashSaleSlotController::class, 'update']);
            Route::delete('/{flashSaleSlot}', [AdminFlashSaleSlotController::class, 'destroy']);
        });
    });

});
