<?php

namespace App\Http\Controllers;

use App\User;
use App\Notifications\AccountConfirmation;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
  /**
   * Activate user's account
   */
  public function activate($token)
  {
    $user = User::where('activation_token', $token)->first();
    // Check if an activation token is found
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

  /**
   * Logout user
   */
  public function logout()
  {
    if (Auth::check()) {
      Auth::user()->token()->revoke();
      return response([
        'error' => false,
        'messages' => ['Vous avez bien été déconnecté']
      ]);
    } else {
      return response([
        'error' => true,
        'messages' => ["Déconnexion impossible, vous n'êtes pas connecté."]
      ]);
    }
  }
}
