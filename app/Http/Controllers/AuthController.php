<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 顯示登入頁
    public function login()
    {
        return Inertia::render('Auth/Login');
    }

    // 顯示註冊頁
    public function register()
    {
        return Inertia::render('Auth/Register');
    }

    // 處理登入
    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // 登入後回到首頁或原本想去的頁面
            return redirect()->route('home');
        }

        throw ValidationException::withMessages([
            'email' => '登入失敗，請檢查帳號密碼。',
        ]);
    }

    // 處理註冊
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8', // password_confirmation
            'phone' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer', // 強制設定為客戶
            'phone' => $request->phone,
        ]);

        Auth::login($user);

        return redirect()->route('home');
    }

    // 登出
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
