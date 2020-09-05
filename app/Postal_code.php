<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Postal_code extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['city_id','created_at', 'updated_at'];
    public function city(){
        return $this->belongsTo('App\City');
    }
    public function activities() {
        return $this->hasMany('App\Activity');
    }
}
