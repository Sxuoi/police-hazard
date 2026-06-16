<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OfficerLoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('officer.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'nrp' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('officer')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('officer.assignments'));
        }

        return back()->withErrors([
            'nrp' => 'NRP atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('nrp');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('officer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('officer.login');
    }
}
