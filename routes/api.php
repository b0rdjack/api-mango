<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 * Public routes
 */
Route::get('admin', 'AdministratorController@login');
Route::post('customer', 'CustomerController@register');
Route::get('customer', 'CustomerController@login');
Route::post('reset_password', 'PasswordResetController@create');

/**
 * Logged routes
 */
Route::group(['middleware' => 'auth:api'], function () {
    Route::post('/user/logout', 'UserController@logout')->middleware('role:customer,professional,admin');
    Route::delete('/customer', 'CustomerController@delete')->middleware('role:customer');
    Route::delete('/professional', 'ProfessionalController@delete')->middleware('role:professional');
});
