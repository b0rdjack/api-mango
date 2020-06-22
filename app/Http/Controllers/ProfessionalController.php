<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;

class Professional extends Controller
{
    public function login(Request $request) {
        return Helper::login($request, 'professional');
    }
}
