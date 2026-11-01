<?php

namespace App\Http\Requests\Auth;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'facility_code' => ['nullable', 'string', 'max:12'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * The facility the person signed in to, or null for a platform operator.
     */
    public ?Membership $membership = null;

    /**
     * Check the password of the facility named by its code. With no code, only platform operators can sign in.
     * Every mismatch gives the same answer, so the form never reveals who works where.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $code = trim((string) $this->input('facility_code'));
        $email = mb_strtolower(trim((string) $this->input('email')));
        $password = (string) $this->input('password');

        if ($code === '') {
            $user = User::query()->where('email', $email)->where('is_platform_admin', true)->where('is_active', true)->first();
            $hash = $user?->password;
        } else {
            $this->membership = Membership::findForSignIn($code, $email);
            $user = $this->membership?->user;
            $hash = $this->membership?->password;
        }

        if (! Hash::check($password, $hash ?? self::dummyHash()) || $user === null || $hash === null) {
            $this->membership = null;
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * A hash to check against when no account matches, so a miss takes as long as a wrong password.
     */
    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make(Str::random(32));
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
