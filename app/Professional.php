<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id'];

    public function activites()
    {
        return $this->hasMany('App\Activity');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
