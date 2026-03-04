<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function ebaySimilarProducts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EbaySimilarProduct::class, 'product_id', 'id');
    }

    public function productTecdocData(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductTecdocData::class, 'product_id', 'id');
    }

    public function productCompatibilities()
    {
        return $this->hasMany(ProductCompatibility::class, 'product_id', 'id');
    }
}
