<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'description', 'location',
        'price_estimate', 'images', 'status',
    ];

    protected $casts = [
        'images' => 'array', // Cast the JSON column to an array
    ];

    public function user()
    {
        return $this->belongsTo(Item::class);
    }

    public function category()
    {
        return $this->belongsTo(Item::class);
    }
}
