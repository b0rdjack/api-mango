<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Quantity extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['created_at', 'updated_at'];

    public function prices(){
        return $this->hasMany('App\Price');
    }
}
