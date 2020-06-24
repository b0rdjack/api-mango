<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['label'];
    protected $hidden = ['created_at', 'updated_at'];

    public function subcategories() {
        return $this->belongsToMany('App\Subcategory');
    }
}
