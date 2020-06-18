<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $fillable = ['label'];

    public function trips(){
        return $this->hasMany('App\Trip');
    }
}
