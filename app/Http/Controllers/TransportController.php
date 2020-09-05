<?php

namespace App\Http\Controllers;

use App\Transport;

class TransportController extends Controller {

  public function index() {
    return request([
      'error' => false,
      'messages' => [''],
      'transports' => Transport::all()
    ]);
  }
}