<?php

namespace App\Http\Controllers;

use App\Trip;

class TripController extends Controller {
  public function index() {
    return response([
      'error' => false,
      'messages' => [''],
      'trips' => Trip::all()
    ]);
  }
}