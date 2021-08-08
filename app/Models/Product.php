<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDFUNDING = 'crowdfunding';

    public static $typeMap = [
        self::TYPE_NORMAL  => '普通商品',
        self::TYPE_CROWDFUNDING => '众筹商品',
    ];

    protected $fillable = [
        'title', 'description', 'image', 'on_sale',
        'rating', 'sold_count', 'review_count', 'price','type'
    ];

    protected $casts = [
        'on_sale' => 'boolean', // on_sale 是一个布尔类型的字段
    ];

    // 与商品SKU关联
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function crowdfunding()
    {
        return $this->hasOne(CrowdfundingProduct::class);
    }

    public function getImageUrlAttribute()
    {
        // 如果 image 字段本身就已经是完整的 url 就直接返回
        if (Str::startsWith($this->attributes['image'], ['http://', 'https://'])) {
            return $this->attributes['image'];
        }
        return \Storage::disk('public')->url($this->attributes['image']);
    }

    public function scopeOnSale($query)
    {
        return $query->where('on_sale', true);
    }

    public function scopeSearch($query, $like)
    {
        return $query->where('title', 'like', $like)
            ->orWhere('description', 'like', $like)
            ->orWhereHas('skus', function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
    }

    public function scopeOrder($query, $order)
    {
        // 是否是以 _asc 或者 _desc 结尾
        if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
            // 如果字符串的开头是这 3 个字符串之一，说明是一个合法的排序值
            if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                // 根据传入的排序值来构造排序参数
                $query->orderBy($m[1], $m[2]);
            }
        }

        return $query;
    }
}
