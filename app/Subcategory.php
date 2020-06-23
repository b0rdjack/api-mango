<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['created_at', 'updated_at', 'pivot'];

    public function categories(){
        return $this->belongsToMany('App\Category');
    }

    public function activities(){
        return $this->belongsToMany('App\Activity');
    }
}
