<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Consumption_method extends Model
{
    protected $fillable = ['label'];
    public function restaurants(){
        return $this->belongsToMany('App\Restaurant');
    }
}
