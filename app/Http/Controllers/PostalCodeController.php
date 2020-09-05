<?php

namespace App\Http\Controllers;

use App\Postal_code;

class PostalCodeController extends Controller {
  public function index() {
    return response([
      'error' => false,
      'messages' => [''],
      'postal_codes' => Postal_code::get()
    ]);
  }
}