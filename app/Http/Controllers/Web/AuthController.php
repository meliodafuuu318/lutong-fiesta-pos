<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        // error_log('[LOGIN-DEBUG] Before Auth::attempt');

        // try {
        //     $authenticated = Auth::attempt(
        //         $request->only('username', 'password')
        //     );

        //     error_log('[LOGIN-DEBUG] Auth::attempt returned');
        // } catch (\Throwable $e) {
        //     error_log('[LOGIN-DEBUG] Exception: '.get_class($e));
        //     error_log('[LOGIN-DEBUG] Message: '.$e->getMessage());

        //     throw $e;
        // }

        if (! Auth::attempt($request->only('username', 'password'))) {
            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
