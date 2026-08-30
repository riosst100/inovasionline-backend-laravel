<?php

use App\Http\Controllers\Api\V1\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\FlashSaleSlotController as AdminFlashSaleSlotController;
use App\Http\Controllers\Api\V1\Admin\SellerApplicationController as AdminSellerApplicationController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\UserVerificationController as AdminUserVerificationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\FlashSaleSlotController;
use App\Http\Controllers\Api\V1\PublicProductController;
use App\Http\Controllers\Api\V1\PublicStoreController;
use App\Http\Controllers\Api\V1\RegionController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\Seller\CategoryController as SellerCategoryController;
use App\Http\Controllers\Api\V1\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\V1\Seller\PromotionController as SellerPromotionController;
use App\Http\Controllers\Api\V1\SellerApplicationController;
use App\Http\Controllers\Api\V1\UserVerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:6,1');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:6,1');
        Route::post('/google', [AuthController::class, 'google'])
            ->middleware('throttle:6,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:6,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::put('/user', [AuthController::class, 'updateProfile']);
            Route::put('/user/address', [AuthController::class, 'updateAddress']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('seller-applications')->group(function () {
            Route::post('/', [SellerApplicationController::class, 'store']);
            Route::get('/me', [SellerApplicationController::class, 'show']);
        });

        Route::prefix('verifications')->group(function () {
            Route::post('/', [UserVerificationController::class, 'store']);
            Route::get('/me', [UserVerificationController::class, 'show']);
        });
    });

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);

    Route::prefix('products')->group(function () {
        Route::get('/flash-sale', [PublicProductController::class, 'flashSale']);
        Route::get('/best-sellers', [PublicProductController::class, 'bestSellers']);
        Route::get('/{product:slug}', [PublicProductController::class, 'show']);
    });

    Route::get('/stores', [PublicStoreController::class, 'index']);
    Route::get('/stores/{slug}', [PublicStoreController::class, 'show']);

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

    Route::get('/banners', [BannerController::class, 'index']);

    Route::prefix('chat')->group(function () {
        Route::get('/global-preview', [ChatController::class, 'globalPreview']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/threads', [ChatController::class, 'index']);
            Route::get('/threads/{thread}/messages', [ChatController::class, 'messages']);
            Route::post('/threads/{thread}/messages', [ChatController::class, 'sendMessage']);
            Route::post('/users/{user}/dm', [ChatController::class, 'startDirectMessage']);
        });
    });

    Route::prefix('cart')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'store']);
        Route::patch('/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('/items/{cartItem}', [CartController::class, 'destroy']);
    });

    Route::prefix('seller')->middleware(['auth:sanctum', 'seller', 'store.owner'])->group(function () {
        Route::prefix('products')->group(function () {
            Route::get('/', [SellerProductController::class, 'index']);
            Route::post('/', [SellerProductController::class, 'store']);
            Route::get('/{product}', [SellerProductController::class, 'show']);
            Route::post('/{product}', [SellerProductController::class, 'update']);
        });

        Route::prefix('promotions')->group(function () {
            Route::get('/', [SellerPromotionController::class, 'index']);
            Route::post('/', [SellerPromotionController::class, 'store']);
        });

        Route::prefix('categories')->group(function () {
            Route::get('/', [SellerCategoryController::class, 'index']);
            Route::post('/', [SellerCategoryController::class, 'store']);
        });
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);

        Route::prefix('seller-applications')->group(function () {
            Route::get('/', [AdminSellerApplicationController::class, 'index']);
            Route::post('/{sellerApplication}/approve', [AdminSellerApplicationController::class, 'approve']);
            Route::post('/{sellerApplication}/reject', [AdminSellerApplicationController::class, 'reject']);
        });

        Route::prefix('verifications')->group(function () {
            Route::get('/', [AdminUserVerificationController::class, 'index']);
            Route::post('/{userVerification}/approve', [AdminUserVerificationController::class, 'approve']);
            Route::post('/{userVerification}/reject', [AdminUserVerificationController::class, 'reject']);
        });

        Route::prefix('flash-sale-slots')->group(function () {
            Route::get('/', [AdminFlashSaleSlotController::class, 'index']);
            Route::post('/', [AdminFlashSaleSlotController::class, 'store']);
            Route::put('/{flashSaleSlot}', [AdminFlashSaleSlotController::class, 'update']);
            Route::delete('/{flashSaleSlot}', [AdminFlashSaleSlotController::class, 'destroy']);
        });

        Route::prefix('banners')->group(function () {
            Route::get('/', [AdminBannerController::class, 'index']);
            Route::post('/', [AdminBannerController::class, 'store']);
            Route::post('/reorder', [AdminBannerController::class, 'reorder']);
            Route::post('/{banner}', [AdminBannerController::class, 'update']);
            Route::delete('/{banner}', [AdminBannerController::class, 'destroy']);
        });

        Route::prefix('categories')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index']);
            Route::post('/', [AdminCategoryController::class, 'store']);
            Route::put('/{category}', [AdminCategoryController::class, 'update']);
            Route::post('/{category}/approve', [AdminCategoryController::class, 'approve']);
        });
    });

});
