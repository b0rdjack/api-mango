<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $fillable = ['label'];

    protected $hidden = ['created_at', 'updated_at'];

    public function trips(){
        return $this->hasMany('App\Trip');
    }
}
