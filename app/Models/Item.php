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
        'price_estimate','status','is_Approved','images'
    ];

    protected $casts = [
        'images' => 'array', /*Cast the JSON column to an array*/
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function offersAsItem()
    {
        // return $this->hasMany(Offer::class, 'item_id');
        return $this->belongsToMany(Offer::class, 'offer_item', 'item_id', 'offer_id');
    }

    public function offersAsOfferedItem()
    {
        return $this->hasMany(Offer::class, 'offered_item_id');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'offer_item', 'item_id', 'offer_id');
    }

    public function itemsWithOffer()
    {
        return $this->hasMany(Offer::class);
    }

}
