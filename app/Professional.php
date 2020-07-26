<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    public function activites()
    {
        return $this->hasMany('App\Activity');
    }

    public function user()
    {
        return $this->hasOne('App/User');
    }
}
