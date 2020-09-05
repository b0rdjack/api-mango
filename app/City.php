<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['created_at', 'updated_at'];

    public function postal_codes(){
      return $this->hasMany('App\Postal_code');
    }
}
