<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Kullanıcı girişi
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Girdiğiniz bilgiler kayıtlarımızla eşleşmiyor.'],
            ]);
        }

        // Son giriş tarihini güncelle
        $user->last_login_at = now();
        $user->save();

        $device = $request->device_name ?? $request->userAgent() ?? 'unknown';
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Giriş başarılı.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_balance' => $user->account_balance,
                'account_currency' => $user->account_currency,
                'account_type' => $user->account_type,
                'demo_account' => $user->demo_account,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Yeni kullanıcı kaydı
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required|accepted',
            'device_name' => 'nullable|string',
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

        $device = $request->device_name ?? $request->userAgent() ?? 'unknown';
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Kayıt başarılı.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_balance' => $user->account_balance,
                'account_currency' => $user->account_currency,
                'account_type' => $user->account_type,
                'demo_account' => $user->demo_account,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Kullanıcı çıkışı
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Çıkış başarılı.',
        ]);
    }

    /**
     * Kullanıcı bilgilerini getir
     */
    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_balance' => $user->account_balance,
                'account_currency' => $user->account_currency,
                'account_type' => $user->account_type,
                'demo_account' => $user->demo_account,
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }

    /**
     * Profil bilgilerini güncelle
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->name = $request->name;

        if ($user->email !== $request->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil güncellendi.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_balance' => $user->account_balance,
                'account_currency' => $user->account_currency,
                'account_type' => $user->account_type,
                'demo_account' => $user->demo_account,
            ],
        ]);
    }

    /**
     * Şifre güncelle
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mevcut şifre doğru değil.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Şifre güncellendi.',
        ]);
    }

    /**
     * Şifre sıfırlama e-postası gönder
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'success',
                'message' => 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
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
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => 'success',
                'message' => 'Şifre sıfırlama başarılı.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
