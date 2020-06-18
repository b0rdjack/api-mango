<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['label'];
    public function categories(){
        return $this->belongsToMany('App\Category');
    }

    public function activities(){
        return $this->belongsToMany('App\Activity');
    }
}
