<?php

namespace App\Http\Controllers;

use App\User;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class AdministratorController extends Controller
{
    public function login(Request $request)
    {
        // Validate parameters
        $validateData = Validator::make($request->all(), [
            'email' => 'email|required',
            'password' => 'required'
        ]);

        // Send error message if at least one parameter fails
        if ($validateData->fails()) {
            return response([
                "error" => true,
                "messages" => $validateData->messages()
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
                    'messages' => 'Invalid Credentials'
                ]);
            } else {
                $user = Auth::user();
                $role_id = Role::where('label', 'administrator')->first()->id;

                // Check if the user is a administrator
                if ($user->role_id == $role_id) {
                    $accessToken = $user->createToken('authToken')->accessToken;
                    $response = [
                        "last_name" => $user->last_name,
                        "first_name" => $user->first_name
                    ];
                    return response([
                        'error' => false,
                        'user' => $response,
                        'access_token' => $accessToken,
                        'token_type' => 'Bearer'
                    ]);
                } else {
                    return response([
                        'error' => true,
                        'messages' => 'Forbidden'
                    ], 403);
                }
            }
        }
    }
}
