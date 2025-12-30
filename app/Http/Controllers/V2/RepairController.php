<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\RepairService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RepairController extends Controller
{
    protected $repairService;

    public function __construct(RepairService $repairService)
    {
        $this->repairService = $repairService;
    }

    // 顯示維修列表 (Inertia)
    public function index()
    {
        $brands = $this->repairService->getBrandsWithHierarchy();
        return Inertia::render('Repair/Index', compact('brands'));
    }

    // 顯示裝置詳情 (Inertia)
    public function show($id)
    {
        $device = $this->repairService->getDeviceWithPrices($id);
        return Inertia::render('Repair/Show', compact('device'));
    }

    // 處理預約 (Inertia Form Post)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'device_model' => 'required|string',
            'message' => 'nullable|string',
        ]);

        $this->repairService->createInquiry($validated);

        // Inertia 會自動處理這裡的 redirect back，並將 flash message 帶給前端
        return back()->with('success', '預約成功！');
    }
}
