<?php

namespace App\Http\Controllers;

use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSucess;
use App\User;
use App\Role;
use App\PasswordReset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{

  /**
   * This function is called only in the API mode
   * Create the password reset request
   */
  public function create(Request $request)
  {
    // Validate parameter
    $validateData = Validator::make($request->all(), [
      'email' => 'required|string|email'
    ]);

    // Send errors if the parameter fails to validate
    if ($validateData->fails()) {
      return response([
        "error" => true,
        "messages" => $validateData->messages()
      ]);
    } else {
      // Check if the user exists
      $user = User::where('email', $request->email)->first();
      if (!$user) {
        return response([
          "error" => true,
          "messages" => "Invalid credentials"
        ]);
      } else {
        // Check if the user isn't an administrator
        $role_id = Role::where('label', 'administrator')->first()->id;
        if ($user->role_id != $role_id) {
          // Mise à jour si une requête était déjà présente sinon l'a créée
          $passwordReset = PasswordReset::updateOrCreate(
            [
              'email' => $user->email,
              'token' => Str::random(60)
            ]
          );

          // Send the e-mail if all went well and the PasswordReset has been created
          if ($passwordReset) {
            $user->notify(new PasswordResetRequest($passwordReset->token));
          }

          return response([
            'error' => false,
            'message' => 'Un lien de réinitialisation a été envoyé par mail !'
          ]);
        } else {
          return response([
            'error' => false,
            'message' => "Vous n'avez pas l'autorisation de changer votre mot de passe."
          ]);
        }
      }
    }
  }

  /**
   * This function is called only on the Web mode.
   * Displays the form if the token is valid
   */
  public function find($token)
  {
    $passwordReset = PasswordReset::where('token', $token)->first();

    // Check wether the token exists
    if (!$passwordReset) {
      return view('reset_password')->with('token', false);
    } else {
      // Check for the validity of the token according the time (token valid for 15 minutes)
      if (Carbon::parse($passwordReset->updated_at)->addMinutes(15)->isPast()) {
        $passwordReset->delete();
        return view('reset_password')->with('token', false);
      }
      return view('reset_password')->with('token', $passwordReset->token);
    }
  }

  /**
   * This function is called only on the Web mode.
   * Changes the password
   */
  public function reset(Request $request, $token)
  {
    // Validate the password
    $validateData = Validator::make($request->all(), [
      'password' => [
        'required',
        'string',
        'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,32}$/',
        'confirmed'
      ]
    ]);

    // Display errors in case the password is not valid
    if ($validateData->fails()) {
      return redirect('reset_password/find/' . $token)
        ->withErrors($validateData)
        ->withInput();
    } else {
      $passwordReset = PasswordReset::where('token', $token)->first();
      // Check if the PasswordReset request exists
      if (!$passwordReset) {
        return view('reset_password_validation')->withModified(false)->withMessage("Ce lien n'est pas valide.");
      }
      // Check if the token is still valid
      elseif (Carbon::parse($passwordReset->updated_at)->addMinutes(15)->isPast()) {
        return view('reset_password_validation')->withModified(false)->withMessage("Ce lien a expiré.");
      } else {
        // Check if the user exists
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user) {
          return view('reset_password_validation')->withModified(false)->withMessage("Ce lien n'est plus valide.");
        } else {
          // Check wether the user is not an administrator
          $role_id = Role::where('label', 'administrator')->first()->id;
          if ($user->role_id != $role_id) {
            // Modify the password and send a e-mail confirmation
            $user->password = Hash::make($request->password);
            $user->save();
            $user->notify(new PasswordResetSucess());
            $passwordReset->delete();
            return view('reset_password_validation')->with('modified', true);
          } else {
            return view('reset_password_validation')->withModified(false)->withMessage("Vous n'avez pas l'autorisation de modifier votre mot de passe.");
          }
        }
      }
    }
  }
}
