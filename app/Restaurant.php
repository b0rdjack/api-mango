<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = ['maximum_capacity'];
    public function activity() {
        return $this->belongsTo('App\Activity');
    }

    public function consumptionMethods(){
        return $this->belongsToMany('App\Consumption_method');
    }
}
