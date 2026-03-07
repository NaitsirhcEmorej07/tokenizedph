<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'access_code' => ['required', 'string', 'max:255'],
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $inputCode = strtoupper(trim($request->access_code));

        $accessCode = DB::table('access_code_tbl')
            ->where('access_code', $inputCode)
            ->first();

        if (!$accessCode) {
            throw ValidationException::withMessages([
                'access_code' => 'Invalid access code.',
            ]);
        }

        if ((int) $accessCode->status !== 0) {
            throw ValidationException::withMessages([
                'access_code' => 'This access code has already been used or is no longer available.',
            ]);
        }

        $now = now();
        $expiredDate = null;

        switch ($accessCode->subscription_plan) {
            case 'trial':
                $expiredDate = $now->copy()->addDays(7);
                break;

            case 'monthly':
                $expiredDate = $now->copy()->addMonth();
                break;

            case 'semiannual':
                $expiredDate = $now->copy()->addMonths(6);
                break;

            case 'annual':
                $expiredDate = $now->copy()->addYear();
                break;

            default:
                throw ValidationException::withMessages([
                    'access_code' => 'This access code has an invalid subscription plan.',
                ]);
        }

        $user = null;

        DB::transaction(function () use ($request, $accessCode, $now, $expiredDate, &$user) {
            $user = User::create([
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'access_code' => $accessCode->access_code,
            ]);

            DB::table('access_code_tbl')
                ->where('id', $accessCode->id)
                ->update([
                    'status'         => 1,
                    'access_date'    => $now,
                    'access_expired' => $expiredDate,
                    'updated_at'     => $now,
                ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}