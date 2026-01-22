<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['reported_user_id', 'reporter_id', 'reason','image'];


    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser()
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

}
