<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['name', 'address', 'siren', 'phone_number', 'longitude', 'latitude', 'opening_hours', 'closing_hours', 'average_time_spent', 'disabled_access'];
    protected $hidden = ['created_at', 'updated_at', 'pivot', 'siren', 'postal_code_id', 'professional_id', 'subcategory_id', 'state_id', 'deleted_at'];

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

    public function postal_code(){
        return $this->belongsTo('App\Postal_code');
    }

    public function state(){
        return $this->belongsTo('App\State');
    }

    public function isAccepted(){
        $state_id = State::where('label', 'Accepted')->first()->id;
        return $this->state_id === $state_id;
    }
}
