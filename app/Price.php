<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = ['amount'];
    protected $hidden = ['created_at', 'updated_at', 'activity_id', 'quantity_id'];

    public function activity(){
        return $this->belongsTo('App\Activity');
    }

    public function quantity(){
        return $this->belongsTo('App\Quantity');
    }
}
