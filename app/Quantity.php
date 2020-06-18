<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Quantity extends Model
{
    protected $fillable = ['label'];

    public function prices(){
        return $this->hasMany('App\Price');
    }
}
