<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Role;

class Helper
{

  /**
   * Log users
   */
  public static function login(Request $request, $role)
  {

    //Validate parameters
    $validator = Validator::make($request->all(), [
      'email' => 'email|required',
      'password' => 'required'
    ]);

    //Send error message if at least one parameter fails validation
    if ($validator->fails()) {
      return response([
        "error" => true,
        "messages" => $validator->messages()
      ]);
    } else {
      $credentials = [
        'email' => $request->email,
        'password' => $request->password,
        'active' => 1,
        'deleted_at' => null
      ];

      // If authentication attempt failed
      if (!Auth::attempt($credentials)) {
        return response([
          'error' => true,
          'messages' => ['Mauvais identifiants de connexion.']
        ]);
      } else {
        $user = Auth::user();
        $role_id = Role::where('label', $role)->first()->id;

        // Check if the user has the correct role
        if ($user->role_id == $role_id) {
          $accessToken = $user->createToken('Personal Access Token')->accessToken;
          return response([
            'error' => false,
            'access_token' => $accessToken,
            'token_type' => 'Bearer'
          ]);
        } else {
          return response([
            'error' => true,
            'messages' => ['Non autorisé.']
          ], 403);
        }
      }
    }
  }

  /**
   * Show profil
   */
  public static function show()
  {
    if (Auth::check()) {
      $user = Auth::user();
      return response([
        'error' => false,
        'user' => $user
      ]);
    } else {
      return response([
        'error' => true,
        'messages' => ["Vous n'êtes pas connecté."]
      ]);
    }
  }
}
