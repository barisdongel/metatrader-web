<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Giriş formunu göster
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Kullanıcı girişi işle
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Son giriş tarihini güncelle
            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.',
        ])->withInput($request->only('email', 'remember'));
    }

    /**
     * Kayıt formunu göster
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Yeni kullanıcı kaydı oluştur
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'account_balance' => 10000, // Demo hesap için başlangıç bakiyesi
            'account_currency' => 'USD', // Varsayılan hesap para birimi
            'account_type' => 'demo', // Demo hesap
            'demo_account' => true,
        ]);

        Auth::login($user);

        return redirect(route('dashboard'));
    }

    /**
     * Kullanıcı çıkışı yap
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Şifre sıfırlama formunu göster
     */
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Şifre sıfırlama e-postası gönder
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Şifre sıfırlama formunu göster
     */
    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Şifreyi sıfırla
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
