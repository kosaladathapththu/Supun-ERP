<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'password' => ['required', 'string'], 'remember' => ['nullable', 'boolean']];
    }

    public function authenticate(): void
    {
        if (! Auth::attempt($this->only('email', 'password') + ['is_active' => true], $this->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'The supplied credentials are invalid or this account is inactive.']);
        }
        $this->session()->regenerate();
        $this->user()->forceFill(['last_login_at' => now()])->save();
    }
}
