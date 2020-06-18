<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = ['amount'];

    public function activity(){
        return $this->belongsTo('App\Activity');
    }

    public function quantities(){
        return $this->hasMany('App\Quantity');
    }
}
