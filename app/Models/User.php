<?php

namespace App\Models;

use App\Support\Enums\UserRole;
use App\Support\Enums\UserVerificationStatus;
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
        'google_id',
        'role',
        'avatar_path',
        'province_code',
        'city_code',
        'district_code',
        'village_code',
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

    public function userVerifications(): HasMany
    {
        return $this->hasMany(UserVerification::class);
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

    public function isPlatformAdmin(): bool
    {
        return $this->role === UserRole::PLATFORM_ADMIN;
    }

    public function isVerified(): bool
    {
        return $this->userVerifications()->where('status', UserVerificationStatus::APPROVED)->exists();
    }

    public function hasAddress(): bool
    {
        return $this->province_code !== null
            && $this->city_code !== null
            && $this->district_code !== null
            && $this->village_code !== null;
    }

    public function chatThreadParticipations(): HasMany
    {
        return $this->hasMany(ChatThreadParticipant::class);
    }
}
