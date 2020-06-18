<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = ['message'];

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

    public function grade(){
        return $this->belongsTo('App\Grade');
    }
}
