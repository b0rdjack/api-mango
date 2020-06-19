<?php

namespace App\Http\Controllers;

use App\User;
use App\Customer;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function register(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'last_name' => 'required|max:55',
            'first_name' => 'required|max:55',
            'email' => 'email|required|unique:users',
            'password' => 'required',
            'date_of_birth' => 'required'
        ]);

        if ($validateData->fails()) {
            return response([
                "error" => true,
                "messages" => $validateData->messages()
            ]);
        } else {
            $validateData = $request->all();
            $date_of_birth = array_pop($validateData);
            $validateData['password'] = Hash::make($validateData['password']);
            $validateData += ['role_id' => Role::where('label', 'customer')->first()->id];
            $user = User::create($validateData);
            $accessToken = $user->createToken('authToken')->accessToken;

            $new_customer = [
                'date_of_birth' => date_create_from_format('Y-m-d H:i:s', $date_of_birth),
                'user_id' => $user->id
            ];
            Customer::create($new_customer);
            $full_customer = [
                'id' => $user->id,
                'last_name' => $user->last_name,
                'first_name' => $user->first_name,
                'email' => $user->email,
                'date_of_birth' => $date_of_birth
            ];

            return response([
                'error' => false,
                'user' => $full_customer,
                'type_of_token' => 'Bearer',
                'access_token' => $accessToken
            ]);
        }
    }

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
                $role_id = Role::where('label', 'customer')->first()->id;
                if ($user->role_id == $role_id) {
                    $accessToken = $user->createToken('authToken')->accessToken;
                    $customer = Customer::where('user_id', $user->id)->first();
                    $full_customer = [
                        'id' => $user->id,
                        'last_name' => $user->last_name,
                        'first_name' => $user->first_name,
                        'email' => $user->email,
                        'date_of_birth' => $customer->date_of_birth
                    ];
                    return response([
                        'error' => false,
                        'user' => $full_customer,
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
