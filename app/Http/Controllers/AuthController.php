<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'nullable|string',
            'login' => 'nullable|string',
            'password' => 'required|string',
            'remember' => 'nullable',
        ]);

        $loginInput = trim($fields['login'] ?? $fields['email'] ?? '');
        if ($loginInput === '') {
            throw ValidationException::withMessages(['email' => 'Email atau nama pengguna wajib diisi.']);
        }

        $remember = $request->boolean('remember');

        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $credentials = [
                'email' => strtolower($loginInput),
                'password' => $fields['password'],
                'is_active' => true,
            ];
            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();

                return $request->user()->role === 'contributor' ? redirect('/me') : redirect()->intended('/');
            }
        } else {
            $user = User::whereRaw('LOWER(name) = ?', [strtolower($loginInput)])
                ->orWhere('email', strtolower($loginInput))
                ->first();

            if ($user && $user->is_active && Hash::check($fields['password'], $user->password)) {
                Auth::login($user, $remember);
                $request->session()->regenerate();

                return $request->user()->role === 'contributor' ? redirect('/me') : redirect()->intended('/');
            }
        }

        throw ValidationException::withMessages(['email' => 'Email/Username atau kata sandi tidak sesuai.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function updatePassword(Request $request)
    {
        $fields = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'new_password.required' => 'Kata sandi baru wajib diisi.',
            'new_password.min' => 'Kata sandi baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = $request->user();

        if (! Hash::check($fields['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini salah.',
            ]);
        }

        $user->update([
            'password' => Hash::make($fields['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kata sandi berhasil diperbarui.',
        ]);
    }
}
