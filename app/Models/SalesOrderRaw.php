<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderRaw extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'data' => 'array',
        'modified_at' => 'datetime',
        'posted_to_taxjar' => 'boolean'
    ];
}
