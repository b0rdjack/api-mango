<?php

namespace App\Http\Controllers;

use App\Professional;
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
                    'error' => true,
                    'messages' => $validator->messages()
                ]);
            } else {
                $user = Auth::user();
                // Check if the password is the correct one
                if (Hash::check($request->password, $user->password)) {
                    $user->token()->revoke();
                    $user->delete();
                    return response([
                        'error' => false,
                        'messages' => ['Vos données ont bien été supprimées.']
                    ]);
                } else {
                    return response([
                        'error' => true,
                        'messages' => ['Le mot de passe est incorrecte.']
                    ]);
                }
            }
        } else {
            return response([
                'error' => true,
                'messages' => "Suppresion impossible, vous n'êtes pas connecté."
            ]);
        }
    }

    public function show($id)
    {
        $professional  = Professional::with('user')->find($id);
        $user = Auth::user();
        if ($professional && $user) {
            if (!$user->isCustomer()) {
                return response([
                    'error' => false,
                    'messages' => [''],
                    'professional' => $professional->load('state')
                ]);
            } else {
                return response([
                    'error' => true,
                    'messages' => ["Le professionel demandé n'a pas encore été accepté"]
                ]);
            }
        } else {
            return response([
                'error' => true,
                'messsages' => ["Ce compte n'existe pas"]
            ]);
        }
    }
}
