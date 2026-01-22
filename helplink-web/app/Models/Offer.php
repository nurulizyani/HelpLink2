<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $primaryKey = 'offer_id';
    protected $table = 'offers';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
    'user_id',
    'item_name',
    'description',
    'quantity',
    'category',
    'address',          // ✅ keep only 'address' (your controller & Flutter use this)
    'latitude',
    'longitude',
    'delivery_type',
    'image',
    'status',
    'rating',
    'comment',
];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class, 'offer_id', 'offer_id');
    }
}
