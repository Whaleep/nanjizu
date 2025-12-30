<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\RepairService;
use Illuminate\Http\Request;

class RepairController extends Controller
{
    protected $repairService;

    public function __construct(RepairService $repairService)
    {
        $this->repairService = $repairService;
    }

    // 顯示所有品牌與裝置 (Blade)
    public function index()
    {
        $brands = $this->repairService->getBrandsWithHierarchy();
        return view('repair.index', compact('brands'));
    }

    // 顯示單一裝置的報價詳情 (Blade)
    public function show($id)
    {
        $device = $this->repairService->getDeviceWithPrices($id);
        return view('repair.show', compact('device'));
    }

    // 處理預約表單提交 (Blade Form Post)
    public function storeInquiry(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'device_model' => 'required|string',
            'message' => 'nullable|string',
        ]);

        $this->repairService->createInquiry($validated);

        return back()->with('success', '您的預約已送出，我們將盡快聯繫您！');
    }
}
