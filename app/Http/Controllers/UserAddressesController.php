<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserAddressRequest;
use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserAddressesController extends Controller
{
    public function index()
    {
        $addresses = request()->user()->userAddresses;
        return view('user_addresses.index', compact('addresses'));
    }

    public function create()
    {
        $address = new UserAddress();
        return view('user_addresses.create_and_edit', compact('address'));
    }

    public function store(UserAddressRequest $request)
    {
        $request->user()->userAddresses()->create($request->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));

        return redirect()->route('user_addresses.index');
    }

    public function edit(UserAddress $userAddress)
    {
        $this->authorize('own', $userAddress);
        $address = $userAddress;
        return view('user_addresses.create_and_edit', compact('address'));
    }

    public function update(UserAddressRequest $request, UserAddress $userAddress)
    {
        $this->authorize('own', $userAddress);
        $userAddress->update($request->only([
            'province',
            'city',
            'district',
            'address',
            'zip',
            'contact_name',
            'contact_phone',
        ]));

        return redirect()->route('user_addresses.index');
    }

    public function destroy(UserAddress $userAddress)
    {
        $this->authorize('own', $userAddress);
        $userAddress->delete();

        return [];
    }
}
