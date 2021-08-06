<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\Order;
use App\Models\UserAddress;
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

    public function store()
    {
        $user  = request()->user();
        $addressId = request()->input('address_id');
        $remark = request()->input('remark');
        $items = request()->input('items');

        $address = UserAddress::find($addressId);
        $addressInfo = [
            'address' => $address->full_address,
            'zip' => $address->zip,
            'contact_name' => $address->contact_name,
            'contact_phone' => $address->contact_phone,
            'remark' => $remark,
            'total_amount' => 0,
        ];

        $order = (new Order($addressInfo))->store($user, $items, $address);

        $this->dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }

}
