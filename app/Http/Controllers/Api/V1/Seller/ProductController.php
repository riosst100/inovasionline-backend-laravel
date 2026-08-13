<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Store;
use App\Services\ProductService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Store $store */
        $store = $request->attributes->get('store');

        $products = $store->products()
            ->with('images')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ApiResponse::paginated($products, ProductResource::class, 'Products retrieved successfully.');
    }

    public function store(ProductRequest $request): JsonResponse
    {
        /** @var Store $store */
        $store = $request->attributes->get('store');

        $product = $this->productService->create(
            $store,
            $request->safe()->except('images'),
            $request->file('images', [])
        );

        return ApiResponse::success(new ProductResource($product), 'Product created successfully.', [], 201);
    }
}
