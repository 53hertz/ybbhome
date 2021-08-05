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

        return view('products.show', compact('product'));
    }
}
