<?php

namespace App\Http\Controllers;

use App\Quantity;
use App\State;
use App\Subcategory;
use App\Tag;

class OptionController extends Controller {
  public function index() {
    return response([
      'error' => false,
      'messages' => [''],
      'subcategories' => Subcategory::get(),
      'tags' => Tag::get(),
      'states' => State::get(),
      'quantities' => Quantity::get()
    ]);
  }
}