<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'image_path',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
