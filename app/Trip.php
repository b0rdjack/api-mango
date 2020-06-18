<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = ['duration', 'max_budget', 'min_budget', 'quantity'];

    public function transport() {
        return $this->belongsTo('App\Transport');
    }

    public function activities() {
        return $this->belongsToMany('App\Activity');
    }
}
