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
        $validateData = Validator::make($request->all(), [
            'email' => 'email|required',
            'password' => 'required'
        ]);
        if ($validateData->fails()) {
            return response([
                "error" => true,
                "messages" => $validateData->messages()
            ]);
        } else {
            if (!Auth::attempt($request->all())) {
                return response([
                    'error' => true,
                    'messages' => 'Invalid Credentials'
                ]);
            } else {
                $user = Auth::user();
                $role_id = Role::where('label', 'administrator')->first()->id;
                if ($user->role_id == $role_id) {
                    $accessToken = $user->createToken('authToken')->accessToken;
                    $response = [
                        "id" => $user->id,
                        "last_name" => $user->last_name
                    ];
                    return response([
                        'error' => false,
                        'user' => $response,
                        'access_token' => $accessToken
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
