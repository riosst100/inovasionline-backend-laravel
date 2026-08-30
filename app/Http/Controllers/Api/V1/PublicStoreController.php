<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProductResource;
use App\Http\Resources\PublicStoreDetailResource;
use App\Http\Resources\PublicStoreResource;
use App\Models\Store;
use App\Support\Enums\OrderStatus;
use App\Support\Enums\ProductStatus;
use App\Support\Enums\StoreStatus;
use App\Support\Responses\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sort' => ['nullable', 'in:nearest,popular,newest'],
            'city_code' => ['required_if:sort,nearest', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $sort = $request->string('sort')->toString() ?: 'newest';
        $limit = $request->integer('limit', 12);

        $query = Store::query()
            ->where('status', StoreStatus::OPEN)
            ->whereNotNull('approved_at')
            ->withCount('products')
            ->withCount(['orders as order_items_sum_quantity' => function ($query) {
                $query->where('status', OrderStatus::COMPLETED);
            }]);

        match ($sort) {
            'nearest' => $this->applyNearest($query, $request->string('city_code')->toString()),
            'popular' => $query->orderByDesc('order_items_sum_quantity'),
            default => $query->orderByDesc('created_at'),
        };

        $stores = $query->limit($limit)->get();

        return ApiResponse::success(PublicStoreResource::collection($stores), 'Stores retrieved successfully.');
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $store = Store::query()
            ->where('slug', $slug)
            ->where('status', StoreStatus::OPEN)
            ->whereNotNull('approved_at')
            ->withCount('products')
            ->withCount(['orders as order_items_sum_quantity' => function ($query) {
                $query->where('status', OrderStatus::COMPLETED);
            }])
            ->with('seller')
            ->firstOrFail();

        $products = $store->products()
            ->where('status', ProductStatus::ACTIVE)
            ->with(['images' => fn ($query) => $query->where('is_primary', true)->orWhere('sort_order', 0)])
            ->withSum(['orderItems as order_items_sum_quantity' => function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('status', OrderStatus::COMPLETED));
            }], 'quantity')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::success([
            'store' => new PublicStoreDetailResource($store),
            'products' => PublicProductResource::collection($products->items()),
        ], 'Store retrieved successfully.', [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    private function applyNearest(Builder $query, string $cityCode): void
    {
        $query->orderByRaw('city_code = ? desc', [$cityCode])
            ->orderByDesc('order_items_sum_quantity')
            ->orderByDesc('created_at');
    }
}
