<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'item_id',
        'offered_item_id',
        'offered_by',
        'message_text',
        'status',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function offeredItem()
    {
        return $this->belongsTo(Item::class, 'offered_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'offered_by');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function offeredItems()
    {
        return $this->belongsToMany(Item::class, 'offer_item', 'offer_id', 'item_id');
    }
}
