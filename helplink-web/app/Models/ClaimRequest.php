<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimRequest extends Model
{
    use HasFactory;

    protected $table = 'claim_requests';

    protected $fillable = [
        'request_id',
        'user_id',
        'status',
        'rating',
        'comment',
    ];

    // 🔗 Relationship: setiap claim_request dimiliki oleh 1 user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔗 Relationship: setiap claim_request merujuk kepada 1 request
    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id', 'id');
    }
}
