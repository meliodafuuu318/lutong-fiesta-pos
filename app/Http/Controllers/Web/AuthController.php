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
        // error_log('[SSL-DEBUG] cwd='.getcwd());
        // error_log('[SSL-DEBUG] root cert=' .
        //     (file_exists('/var/task/certs/ca.pem') ? 'yes' : 'no'));

        // error_log('[SSL-DEBUG] php cert=' .
        //     (file_exists('/var/task/php/certs/ca.pem') ? 'yes' : 'no'));

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
