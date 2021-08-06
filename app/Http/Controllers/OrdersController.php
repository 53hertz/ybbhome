<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Http\Requests\OrderRequest;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function index()
    {
        $orders = Order::query()
            // 使用 with 方法预加载，避免N + 1问题
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', request()->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('orders.index', compact('orders'));
    }

    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user    = $request->user();
        $remark  = $request->input('remark');
        $items   = $request->input('items');
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($user, $address, $remark, $items);
    }

    public function show(Order $order)
    {
        $this->authorize('own', $order);
        $order = $order->load(['items.productSku', 'items.product']);

        return view('orders.show', compact('order'));
    }

}
