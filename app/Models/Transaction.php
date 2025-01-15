<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'offer_id',
        'initiator_id',
        'recipient_id',
        'status',
        'completed_at',
        'cancelled_at',
        'disputed_at'
    ];
}
