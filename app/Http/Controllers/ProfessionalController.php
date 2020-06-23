<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Professional extends Controller
{
    public function login(Request $request)
    {
        return Helper::login($request, 'professional');
    }

    /**
     * Delete a professional (SoftDelete)
     */
    public function delete(Request $request)
    {
        if (Auth::check()) {
            //Validate parameters
            $validator = Validator::make($request->all(), [
                'password' => 'required'
            ]);
            if ($validator->fails()) {
                return response([
                    "error" => true,
                    "messages" => $validator->messages()
                ]);
            } else {
                $user = Auth::user();
                // Check if the password is the correct one
                if (Hash::check($request->password, $user->password)) {
                    $user->token()->revoke();
                    $user->delete();
                    return response([
                        'error' => false,
                        'message' => 'Vos données ont bien été supprimées.'
                    ]);
                } else {
                    return response([
                        'error' => true,
                        'message' => "Le mot de passe est incorrecte"
                    ]);
                }
            }
        } else {
            return response([
                'error' => true,
                'message' => "Suppresion impossible, vous n'êtes pas connecté."
            ]);
        }
    }
}
