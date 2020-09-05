<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['number'];

    public function feedback()
    {
        return $this->hasMany('App\Grade');
    }
}
