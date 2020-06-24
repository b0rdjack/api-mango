<?php

namespace App\Http\Controllers;

use App\Activity;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller {

  public function index(){
    return Activity::with('subcategory')->with('professional')->with('tags')->get();
  }

  public function show($id){
    Log::info(Activity::findOrFail($id)->name);
    return Activity::with('subcategory')->with('professional')->with('tags')->findOrFail($id);
  }
}