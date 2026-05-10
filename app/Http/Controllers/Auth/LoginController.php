<?php

namespace App\Http\Controllers\Auth;

use App\Actions\AuthenticateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUser,
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $this->authenticateUser->execute(
                nrp: $request->validated('nrp'),
                password: $request->validated('password'),
                ip: $request->ip(),
                userAgent: $request->userAgent(),
            );

            $request->clearRateLimiter();
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $request->hitRateLimiter();
            throw $e;
        }
    }
}
