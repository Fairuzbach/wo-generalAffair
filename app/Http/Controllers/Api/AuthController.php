<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // API LOGIN
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Cek apakah email & password benar?
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah'
            ], 401); // 401 = Unauthorized
        }

        // 3. Jika benar, ambil data user & buat Token
        $user = User::where('email', $request->email)->firstOrFail();

        // 'auth_token' adalah nama token (bebas), token ini yang dipakai nanti
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'access_token' => $token, // <-- INI KUNCI RAHASIANYA
                'token_type' => 'Bearer'
            ]
        ]);
    }

    // API LOGOUT
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai (agar tidak bisa dipakai lagi)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout Berhasil'
        ]);
    }
}
