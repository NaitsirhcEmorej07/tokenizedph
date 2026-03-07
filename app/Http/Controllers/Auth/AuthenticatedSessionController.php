<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Step 1: Find user first using email
        $user = DB::table('users')->where('email', $request->email)->first();

        // Step 2: If user exists and has access_code, check subscription expiry
        if ($user && !empty($user->access_code)) {
            $accessCode = DB::table('access_code_tbl')
                ->where('access_code', $user->access_code)
                ->first();

            if ($accessCode) {
                // If access_expired is not null and already expired
                if (!empty($accessCode->access_expired) && now()->gt($accessCode->access_expired)) {
                    return back()->withErrors([
                        'subscription' => 'Your subscription has expired. Please renew your access.',
                    ])->onlyInput('email');
                }
            }
        }

        // Step 3: Proceed with normal login
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
