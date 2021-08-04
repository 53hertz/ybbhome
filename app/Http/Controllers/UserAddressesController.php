<?php

namespace App\Http\Controllers;

use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressesController extends Controller
{
    public function index()
    {
        $addresses = UserAddress::all();
        return view('user_addresses.index', compact('addresses'));
    }
}
