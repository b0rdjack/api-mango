<?php

namespace App\Http\Controllers;

use App\Category;

class CategoryController extends Controller {
  public function index() {
    return response([
      'error' => false,
      'messages' => [''],
      'categories' => Category::with('subCategories')->get()
    ]);
  }
}