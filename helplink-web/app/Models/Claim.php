<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $table = 'claims';

    protected $fillable = [
        'user_id',
        'offer_id',
        'request_id', // ✅ Tambah ni
        'status',
        'rating',
        'comment',
    ];

    // Claim ini dimiliki oleh satu user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Claim ini mungkin berkaitan dengan satu offer
    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id');
    }

    // ✅ Claim ini mungkin juga berkaitan dengan satu request
    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }
}
