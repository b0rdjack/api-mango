<?php

namespace App\Http\Controllers;

use App\Activity;

class ActivityController extends Controller {
  public function index(){
    return Activity::with('subCategory')->get();
  }
}