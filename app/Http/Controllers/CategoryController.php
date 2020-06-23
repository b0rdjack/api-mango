<?php

namespace App\Http\Controllers;

use App\Category;

class CategoryController extends Controller {
  public function index() {
    return Category::with('subCategories')->get();
  }
}