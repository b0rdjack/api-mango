<?php

namespace App\Http\Controllers;

use App\User;
use App\Notifications\AccountConfirmation;

class UserController extends Controller
{
  public function activate($token)
  {
    $user = User::where('activation_token', $token)->first();
    if (!$user) {
      return view('activation_account')->with('activated', false);
    } else {
      $user->active = true;
      $user->activation_token = "";
      $user->email_verified_at = \Carbon\Carbon::now()->timestamp;
      $user->save();
      $user->notify(new AccountConfirmation($user));
      return view('activation_account')->with('activated', true);

    }
  }
}
