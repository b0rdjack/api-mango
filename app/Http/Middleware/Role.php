<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param $roles
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return response([
                'error' => true,
                'message' => 'Veuillez vous connecter pour accéder à ces informations.'
            ]);
        } else {
            $user = Auth::user();
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return $next($request);
                }
            }
        }
        return response([
            'error' => true,
            'message' => 'Veuillez vous connecter pour accéder à ces informations.'
        ]);
    }
}
