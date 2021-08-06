<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $service)
    {
        $this->cartService = $service;
    }

    public function index()
    {
        $cartItems = $this->cartService->get();
        $addresses = request()->user()->userAddresses()->orderBy('last_used_at', 'desc')->get();

        return view('cart.index', compact('cartItems', 'addresses'));
    }

    public function add(AddCartRequest $request)
    {
        $skuId = $request->input('sku_id');
        $amount = $request->input('amount');

        $this->cartService->add($skuId, $amount);

        return [];
    }

    public function remove(ProductSku $sku)
    {
        $this->cartService->remove($sku->id);

        return [];
    }
}
