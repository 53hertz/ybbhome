<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\ProductService;
use Carbon\Exceptions\ParseErrorException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index()
    {
        $page    = request()->input('page', 1);
        $perPage = 16;
        // 新建查询构造器对象，设置只搜索上架商品，设置分页
        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

        if (request()->input('category_id') && $category = Category::find(request()->input('category_id'))) {
            // 调用查询构造器的类目筛选
            $builder->category($category);
        }

        if ($search = request()->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));
            // 调用查询构造器的关键词筛选
            $builder->keywords($keywords);
        }

        if ($search || isset($category)) {
            // 调用查询构造器的分面搜索
            $builder->aggregateProperties();
        }

        $propertyFilters = [];
        if ($filterString = request()->input('filters')) {
            $filterArray = explode('|', $filterString);
            foreach ($filterArray as $filter) {
                list($name, $value) = explode(':', $filter);
                $propertyFilters[$name] = $value;
                // 调用查询构造器的属性筛选
                $builder->propertyFilter($name, $value);
            }
        }

        if ($order = request()->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // 调用查询构造器的排序
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        // 最后通过 getParams() 方法取回构造好的查询参数
        $result = app('es')->search($builder->getParams());

        // 通过 collect 函数将返回结果转为集合，并通过集合的 pluck 方法取到返回的商品 ID 数组
        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();
        // 通过 whereIn 方法从数据库中读取商品数据
        $products = Product::query()->byIds($productIds)->get();
        // 返回一个 LengthAwarePaginator 对象
        $pager = new LengthAwarePaginator($products, $result['hits']['total']['value'], $perPage, $page, [
            'path' => route('products.index', false), // 手动构建分页的 url
        ]);

        $properties = [];
        // 如果返回结果里有 aggregations 字段，说明做了分面搜索
        if (isset($result['aggregations'])) {
            // 使用 collect 函数将返回值转为集合
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function ($bucket) {
                    // 通过 map 方法取出我们需要的字段
                    return [
                        'key'    => $bucket['key'],
                        'values' => collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })
                ->filter(function ($property) use ($propertyFilters) {
                    // 过滤掉只剩下一个值 或者 已经在筛选条件里的属性
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]) ;
                });
        }

        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
        ]);

//        $builder  = Product::onSale();
//
//        if ($search = request()->input('search', '')) {
//            $like = '%'.$search.'%';
//            $builder->search($like);
//        }
//
//        // 如果有传入 category_id 字段，并且在数据库中有对应的类目
//        if (request()->input('category_id') && $category = Category::find(request()->input('category_id'))) {
//            // 如果这是一个父类目
//            if ($category->is_directory) {
//                // 则筛选出该父类目下所有子类目的商品
//                $builder->whereHas('category', function ($query) use ($category) {
//                    // 这里的逻辑参考本章第一节
//                    $query->where('path', 'like', $category->path.$category->id.'-%');
//                });
//            } else {
//                // 如果这不是一个父类目，则直接筛选此类目下的商品
//                $builder->where('category_id', $category->id);
//            }
//        }
//
//        if ($order = request()->input('order', '')) {
//            $builder->order($order);
//        }
//
//        $products = $builder->paginate(16);

//        return view('products.index', [
//            'products' => $products,
//            'filters'  => [
//                'search' => $search,
//                'order'  => $order,
//            ],
//            'category' => $category ?? null,
//        ]);
    }

    public function show(Product $product, ProductService $service)
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

        // 创建一个查询构造器，只搜索上架的商品，取搜索结果的前 4 个商品
        $builder = (new ProductSearchBuilder())->onSale()->paginate(4, 1);
        // 遍历当前商品的属性
        foreach ($product->properties as $property) {
            // 添加到 should 条件中
            $builder->propertyFilter($property->name, $property->value, 'should');
        }
        // 设置最少匹配一半属性
        $builder->minShouldMatch(ceil(count($product->properties) / 2));
        $params = $builder->getParams();
        // 同时将当前商品的 ID 排除
        $params['body']['query']['bool']['must_not'] = [['term' => ['_id' => $product->id]]];
        // 搜索
        $result = app('es')->search($params);
        $similarProductIds = $service->getSimilarProductIds($product, 4);
        $similarProducts   = Product::query()->byIds($similarProductIds)->get();
        // 最后别忘了注入到模板中
        return view('products.show', [
            'product' => $product,
            'favored' => $favored,
            'reviews' => $reviews,
            'similar' => $similarProducts,
        ]);
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
