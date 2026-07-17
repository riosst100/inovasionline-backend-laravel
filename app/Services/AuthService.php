<?php

namespace App\Services;

use App\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::CUSTOMER,
            ]);

            event(new Registered($user));

            return $user;
        });
    }
}
