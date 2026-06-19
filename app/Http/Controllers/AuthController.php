<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function register(Request $request) {
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'role' => 'required' // admin, operator, atau customer
    ]);

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
    ]);

    return response()->json(['success' => true, 'message' => 'User berhasil didaftarkan!']);
}
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            return response()->json([
                'success' => true,
                'user' => $user,
                'message' => 'Login Berhasil'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Email atau Password salah'], 401);
    }

    public function logout() {
        Auth::logout();
        return response()->json(['success' => true]);
    }

    
    public function updatePassword(Request $request) {
        $request->validate([
            'user_id' => 'required',
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed', // Harus ada input new_password_confirmation
        ]);

        $user = \App\Models\User::find($request->user_id);

        // 1. Cek apakah password lama benar
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Password lama tidak sesuai!'], 422);
        }

        // 2. Update ke password baru
        $user->update([
            'password' => \Hash::make($request->new_password)
        ]);

        // 3. Catat di Audit Trail
        DB::table('audit_trail')->insert([
            'id_user'   => $user->id,
            'aktivitas' => "User memperbarui kata sandi akun.",
            'waktu_log' => now(),
            'created_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Password berhasil diperbarui!']);
    }
}
