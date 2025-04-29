<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'comment',
    ];

    // In App\Models\Review.php

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

}
