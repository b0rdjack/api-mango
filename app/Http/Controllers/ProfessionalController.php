<?php

namespace App\Http\Controllers;

use App\Professional;
use App\Role;
use App\User;
use App\Notifications\SignupActivate;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Cartalyst\Stripe\Stripe;
use Exception;
use Illuminate\Support\Facades\Log;

class ProfessionalController extends Controller
{
    public function login(Request $request)
    {
        return Helper::login($request, 'professional');
    }

    public function register(Request $request)
    {
        // Validate parameters
        $validateData = Validator::make($request->all(), [
            'last_name' => 'required|max:55|min:2',
            'first_name' => 'required|max:55|min:2',
            'email' => 'email|required|unique:users',
            'password' => [
                'required',
                'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,32}$/',
                'confirmed'
            ],
            'card' => 'required',
            'card.number' => 'required|min:13|max:19',
            'card.exp_month' => 'required|min:1|max:2',
            'card.exp_year' => 'required|size:4',
            'card.cvc' => 'required|min:3|max:4'
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
                'role_id' => Role::where('label', 'professional')->first()->id,
                'activation_token' => Str::random(60)
            ]);
            $user->save();

            // Create professional
            $professional = new Professional([
                'user_id' => $user->id
            ]);

            /**
             * STRIPE CONFIGURATION
             * https://cartalyst.com/manual/stripe/2.0
             */

            // Instanciate the Stripe object
            $stripe = new Stripe();

            // Create the Stripe customer
            $stripe_customer = $stripe->customers()->create([
                "email" => $request->email
            ]);
            try {
                // Create the payment method
                $paymentMethod = $stripe->paymentMethods()->create([
                    'type' => 'card',
                    'card' => [
                        'number' => $request->card["number"],
                        'exp_month' => $request->card["exp_month"],
                        'exp_year' => $request->card["exp_year"],
                        'cvc' => $request->card["cvc"]
                    ],
                ]);

                // Attach the payment method to the Stripe customer
                $stripe->paymentMethods()->attach($paymentMethod["id"], $stripe_customer["id"]);

                // Set the payment method as default for the Stripe customer
                $stripe->customers()->update($stripe_customer["id"], [
                    "invoice_settings" => [
                        "default_payment_method" => $paymentMethod["id"]
                    ]
                ]);

                // Create the subscription the the plan for the Stripe Customer
                $stripe->subscriptions()->create($stripe_customer["id"], ['plan' => 'price_1HAHJCKtrNnV8nQs0BcNV2vX']);

                // Save the Stripe customer id
                $professional->stripe_id = $stripe_customer["id"];
            } catch (Exception $e) {
                Log::info($e);
                $professional->delete();
                $user->delete();
                $stripe->customers()->delete($stripe_customer["id"]);
                return response([
                    'error' => true,
                    'messages' => ["Les coordonnées bancaires saisies ne sont pas valide."]
                ]);
            }

            $professional->save();

            // Send e-mail for account confirmation
            $user->notify(new SignupActivate($user));

            return response([
                'error' => false,
                'messages' => ["Un mail viens d'être envoyé à l'adresse e-mail suivante: " . $request->email]
            ]);
        }
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
