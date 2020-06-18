<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['label'];
    public function acitivites()
    {
        return $this->belongsToMany('App\Activity');
    }
}
