<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms_agreed' => ['required', 'accepted'],
            'province_code' => ['required', 'string', 'exists:indonesia_provinces,code'],
            'city_code' => ['required', 'string', 'exists:indonesia_cities,code'],
            'district_code' => ['required', 'string', 'exists:indonesia_districts,code'],
            'village_code' => ['required', 'string', 'exists:indonesia_villages,code'],
        ];
    }
}
