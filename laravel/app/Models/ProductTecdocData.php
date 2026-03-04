<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTecdocData extends Model
{
    protected $table = 'product_tecdoc_data';

    protected $casts = [
        'misc' => 'array',
        'article_text' => 'array',
        'gtins' => 'array',
        'trade_numbers' => 'array',
        'replaces_articles' => 'array',
        'replaced_by_articles' => 'array',
        'generic_articles' => 'array',
        'article_criteria' => 'array',
        'linkages' => 'array',
        'pdfs' => 'array',
        'comparable_numbers' => 'array',
        'search_query_matches' => 'array',
        'links' => 'array',
        'prices' => 'array',
    ];
}
