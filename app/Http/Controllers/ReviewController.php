<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $productId = $request->product_id;

        // 1. 檢查是否已評價過
        if (Review::where('user_id', $user->id)->where('product_id', $productId)->exists()) {
            return back()->with('error', '您已經評價過此商品了。');
        }

        // 2. 檢查是否購買過且訂單已完成 (Verified Purchase)
        $hasPurchased = $user->orders()
            ->where('status', 'completed') // 必須是已完成的訂單
            ->whereHas('items', function ($query) use ($productId) {
                // 檢查訂單明細中的規格是否屬於該商品
                $query->whereHas('variant', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                });
            })
            ->exists();

        if (!$hasPurchased) {
            return back()->with('error', '只有購買過此商品且訂單完成的會員才能評價喔！');
        }

        // 3. 建立評價
        Review::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_visible' => true,
        ]);

        return back()->with('success', '評價提交成功！感謝您的回饋。');
    }
}
