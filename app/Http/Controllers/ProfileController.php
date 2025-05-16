<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Profil düzenleme sayfasını göster
     */
    public function edit()
    {
        return view('profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Profil bilgilerini güncelle
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        $user = Auth::user();
        $user->name = $request->name;

        if ($user->email !== $request->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'profile-updated');
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

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mevcut şifreniz doğru değil.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('status', 'password-updated');
    }
}
