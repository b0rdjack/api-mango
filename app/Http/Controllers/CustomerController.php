<?php

namespace App\Http\Controllers;

use App\User;
use App\Customer;
use App\Notifications\SignupActivate;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Helpers\Helper;

class CustomerController extends Controller
{
    public function register(Request $request)
    {
        // Validate parameters
        $validateData = Validator::make($request->all(), [
            'last_name' => 'required|max:55',
            'first_name' => 'required|max:55',
            'email' => 'email|required|unique:users',
            'password' => 'required',
            'date_of_birth' => 'required'
        ]);

        // Send error message if at least one parameter fails validation
        if ($validateData->fails()) {
            return response([
                "error" => true,
                "messages" => $validateData->messages()
            ]);
        } else {

            // Create user
            $user = new User([
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => Role::where('label', 'customer')->first()->id,
                'activation_token' => Str::random(60)
            ]);
            $user->save();

            // Create customer
            $customer = new Customer([
                'date_of_birth' => date_create_from_format('Y-m-d H:i:s', $request->date_of_birth),
                'user_id' => $user->id
            ]);
            $customer->save();

            // Send e-mail for account confirmation
            $user->notify(new SignupActivate($user));

            return response([
                'error' => false,
                'message' => 'Veuillez confirmer votre compte.'
            ]);
        }
    }

    public function login(Request $request)
    {
        return Helper::login($request, 'customer');
    }
}
