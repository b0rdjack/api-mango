<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['date_of_birth'];
    public function user() {
        return $this->belongsTo('App\User');
    }
}
