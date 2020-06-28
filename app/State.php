<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['created_at', 'updated_at'];

    public function activites()
    {
        return $this->hasMany('App\Activity');
    }
}
