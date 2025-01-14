<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsVerified extends Model
{
    use HasFactory;

    public $table = 'user_verifications';

    protected $fillable = [
        'user_id',
        'profile_picture',
        'cnic_front',
        'cnic_back',
        'admin_comment',
    ];
}
