<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    // 1. 會員總覽 (Dashboard)
    public function index()
    {
        // 抓取最近 5 筆訂單
        $recentOrders = auth()->user()->orders()
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'recentOrders' => $recentOrders
        ]);
    }

    // 2. 訂單列表 (Orders)
    public function orders()
    {
        $orders = auth()->user()->orders()
            ->latest()
            ->paginate(10);

        return Inertia::render('Dashboard/Orders', [
            'orders' => $orders
        ]);
    }

    // 3. 訂單詳情 (Order Detail)
    public function orderDetail($orderNumber)
    {
        $order = auth()->user()->orders()
            ->where('order_number', $orderNumber)
            ->with('items') // 預載明細
            ->firstOrFail();

        return Inertia::render('Dashboard/OrderDetail', [
            'order' => $order
        ]);
    }

    // 4. 個人資料頁 (Profile)
    public function profile()
    {
        return Inertia::render('Dashboard/Profile', [
            'user' => auth()->user()
        ]);
    }

    // 5. 更新個人資料
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            // 如果有填密碼才驗證
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'];
        $user->address = $validated['address'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return back()->with('success', '資料已更新');
    }
}

