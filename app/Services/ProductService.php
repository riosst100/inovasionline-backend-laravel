<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Support\Enums\ProductStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  UploadedFile[]  $images
     */
    public function create(Store $store, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($store, $data, $images) {
            $product = $store->products()->create([
                ...$data,
                'slug' => $this->generateSlug($store, $data['name']),
                'status' => $data['status'] ?? ProductStatus::DRAFT->value,
            ]);

            foreach ($images as $index => $image) {
                $path = $image->store('products', 'public');

                $product->images()->create([
                    'path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }

            return $product->load('images');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  UploadedFile[]  $images
     */
    public function update(Product $product, array $data, array $images = []): Product
    {
        return DB::transaction(function () use ($product, $data, $images) {
            $product->update($data);

            if ($images !== []) {
                $nextSortOrder = $product->images()->max('sort_order') + 1;
                $hasPrimary = $product->images()->where('is_primary', true)->exists();

                foreach ($images as $index => $image) {
                    $path = $image->store('products', 'public');

                    $product->images()->create([
                        'path' => $path,
                        'is_primary' => ! $hasPrimary && $index === 0,
                        'sort_order' => $nextSortOrder + $index,
                    ]);
                }
            }

            return $product->load('images');
        });
    }

    private function generateSlug(Store $store, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while ($store->products()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
