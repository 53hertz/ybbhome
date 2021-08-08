<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
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

        // 如果有传入 category_id 字段，并且在数据库中有对应的类目
        if (request()->input('category_id') && $category = Category::find(request()->input('category_id'))) {
            // 如果这是一个父类目
            if ($category->is_directory) {
                // 则筛选出该父类目下所有子类目的商品
                $builder->whereHas('category', function ($query) use ($category) {
                    // 这里的逻辑参考本章第一节
                    $query->where('path', 'like', $category->path.$category->id.'-%');
                });
            } else {
                // 如果这不是一个父类目，则直接筛选此类目下的商品
                $builder->where('category_id', $category->id);
            }
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
            'category' => $category ?? null,
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

        $reviews = OrderItem::query()
            ->with(['order.user', 'productSku']) // 预先加载关联关系
            ->where('product_id', $product->id)
            ->whereNotNull('reviewed_at') // 筛选出已评价的
            ->orderBy('reviewed_at', 'desc') // 按评价时间倒序
            ->limit(10) // 取出 10 条
            ->get();

        return view('products.show', compact('product', 'favored', 'reviews'));
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

    public function favorites()
    {
        $products = request()->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', compact('products'));
    }
}
