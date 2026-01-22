<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimOffer extends Model
{
    use HasFactory;

    protected $table = 'claim_offers';

    protected $fillable = [
        'offer_id',
        'user_id',
        'status',
        'rating',
        'comment',
    ];

    // 🔗 Claimer (User yang claim offer)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🔗 Offer yang di-claim
    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }
}
