<?php

namespace App\Http\Controllers;

use App\Transport;

class TransportController extends Controller {

  public function index() {
    return Transport::all();
  }
}