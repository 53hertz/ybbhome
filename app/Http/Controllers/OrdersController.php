<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
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

        return (new Order($addressInfo))->store($user, $items, $address);
    }
    
}
