<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\DeviceModel;
use App\Models\Inquiry;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    // 顯示所有品牌與裝置
    public function index()
    {
        // 修改：同時載入 deviceCategories 和 deviceModels
        // 這樣不管型號有沒有分類，都能被抓出來
        $brands = Brand::with([
            'deviceCategories',
            'deviceModels' => function ($query) {
                $query->orderBy('sort_order', 'asc'); // 確保型號有排序
            }
        ])
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('repair.index', compact('brands'));
    }


    // 顯示單一裝置的報價詳情
    public function show($id)
    {
        // 這裡使用 Closure 進行進階關聯載入與排序
        $device = DeviceModel::with(['brand', 'prices' => function ($query) {
            // join repair_items 資料表以便使用 sort_order 排序
            $query->join('repair_items', 'device_repair_prices.repair_item_id', '=', 'repair_items.id')
                ->select('device_repair_prices.*') // 確保只選取價格表的欄位，避免 ID 衝突
                ->orderBy('repair_items.sort_order', 'asc');
        }, 'prices.repairItem'])
            ->findOrFail($id);

        return view('repair.show', compact('device'));
    }

    // 處理預約表單提交
    public function storeInquiry(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'device_model' => 'required|string',
            'message' => 'nullable|string',
        ]);

        Inquiry::create($validated);

        return back()->with('success', '您的預約已送出，我們將盡快聯繫您！');
    }
}
