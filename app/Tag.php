<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['created_at', 'updated_at', 'pivot'];

    public function acitivites()
    {
        return $this->belongsToMany('App\Activity');
    }
}
