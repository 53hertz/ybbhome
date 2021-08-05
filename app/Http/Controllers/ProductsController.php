<?php

namespace App\Http\Controllers;

use App\Models\Product;
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
}
