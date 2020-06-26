<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['name', 'address', 'city', 'postal_code', 'siren', 'phone_number', 'longitude', 'latitude', 'opening_hours', 'closing_hours', 'average_time_spent', 'disabled_access'];
    protected $hidden = ['created_at', 'updated_at', 'pivot', 'siren'];

    public function subcategory()
    {
        return $this->belongsTo('App\Subcategory');
    }

    public function prices()
    {
        return $this->hasMany('App\Price');
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag');
    }

    public function trips()
    {
        return $this->belongsToMany('App\Trips');
    }

    public function professional(){
        return $this->belongsTo('App\Professional');
    }
}
