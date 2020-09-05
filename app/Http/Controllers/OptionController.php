<?php

namespace App\Http\Controllers;

use App\Quantity;
use App\State;
use App\Subcategory;
use App\Tag;
use Illuminate\Support\Facades\Auth;

class OptionController extends Controller
{
  public function index()
  {
    if (Auth::check()) {
      $user = Auth::user();
      $res = [
        'error' => false,
        'messages' => [''],
        'subcategories' => Subcategory::get(),
        'tags' => Tag::get(),
        'quantities' => Quantity::get()
      ];
      if ($user->isAdministrator()) {
        $res['states'] =  State::get();
      }
      return response($res);
    }
  }
}
