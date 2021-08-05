<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Product;
use Carbon\Exceptions\ParseErrorException;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index()
    {
        $builder  = Product::onSale();

        if ($search = request()->input('search', '')) {
            $like = '%'.$search.'%';
            $builder->search($like);
        }

        if ($order = request()->input('order', '')) {
            $builder->order($order);
        }

        $products = $builder->paginate(16);

        return view('products.index', [
            'products' => $products,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
        ]);
    }

    public function show(Product $product)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('该商品还未上架哦~');
        }

        $favored = false;
        if($user = request()->user()) {
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        return view('products.show', compact('product', 'favored'));
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product, Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }
}
