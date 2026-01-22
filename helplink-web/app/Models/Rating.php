<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rating extends Model
{
    use HasFactory;

    protected $primaryKey = 'rating_id';
    protected $table = 'ratings';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'offer_id',
        'request_id',
        'rating_value',
        'comment',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }

    public function request()
    {
        return $this->belongsTo(HelpRequest::class, 'request_id'); // jika model request kau nama lain, tukar sini
    }
}
