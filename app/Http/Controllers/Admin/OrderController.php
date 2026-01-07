<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function print(Order $order)
    {
        return view('admin.orders.print', compact('order'));
    }
}
