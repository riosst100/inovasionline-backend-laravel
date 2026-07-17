<?php

namespace App\Models;

use App\Support\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function sellerApplications(): HasMany
    {
        return $this->hasMany(SellerApplication::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function storeMemberships(): HasMany
    {
        return $this->hasMany(StoreMember::class);
    }

    public function isSeller(): bool
    {
        return in_array($this->role, [UserRole::SELLER_OWNER, UserRole::SELLER_STAFF], true);
    }
}
