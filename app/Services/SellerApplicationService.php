<?php

namespace App\Services;

use App\Models\SellerApplication;
use App\Models\User;
use App\Support\Enums\SellerApplicationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SellerApplicationService
{
    public function apply(User $user, array $data): SellerApplication
    {
        if ($user->seller) {
            throw new RuntimeException('You are already a registered seller.');
        }

        if ($user->sellerApplications()->where('status', SellerApplicationStatus::PENDING)->exists()) {
            throw new RuntimeException('You already have a pending seller application.');
        }

        return DB::transaction(function () use ($user, $data) {
            return $user->sellerApplications()->create([
                ...$data,
                'store_slug' => $this->generateStoreSlug($data['store_name']),
                'status' => SellerApplicationStatus::PENDING,
            ]);
        });
    }

    private function generateStoreSlug(string $storeName): string
    {
        $base = Str::slug($storeName);
        $slug = $base;
        $suffix = 1;

        while (SellerApplication::where('store_slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
