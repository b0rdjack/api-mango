<?php

namespace App\Http\Controllers;

use App\Transport;
use App\Tag;
use App\Subcategory;

class FilterController extends Controller {
  public function index() {
    return response([
      'error' => false,
      'messages' => [''],
      'transports' => Transport::all(),
      'tags' => Tag::all(),
      'subcategories' => Subcategory::all()
    ]);
  }
}