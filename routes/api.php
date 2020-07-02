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

    /**
     * Account management
     */
    Route::post('/user/logout', 'UserController@logout')->middleware('role:customer,professional,admin');
    Route::delete('/customer', 'CustomerController@delete')->middleware('role:customer');
    Route::delete('/professional', 'ProfessionalController@delete')->middleware('role:professional');

    /**
     * Transport
     */
    Route::get('/transports', 'TransportController@index')->middleware('role:customer');

    /**
     * Category
     */
    Route::get('/categories', 'CategoryController@index')->middleware('role:customer');

    /**
     * Activity
     */
    Route::get('/activities', 'ActivityController@index')->middleware('role:administrator');
    Route::get('/activities/{id}', 'ActivityController@show')->middleware('role:administrator');
    Route::put('/activities/{id}', 'ActivityController@update')->middleware('role:administrator');
    Route::post('/activities', 'ActivityController@store')->middleware('role:administrator');

    // State
    Route::get('/activities/{id}/accept', 'ActivityController@accept')->middleware('role:administrator');
    Route::get('/activities/{id}/deny', 'ActivityController@deny')->middleware('role:administrator');
    Route::get('/activities/{id}/pend', 'ActivityController@pend')->middleware('role:administrator');
});
