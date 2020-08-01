<?php

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
 * ============================================= Login and Register =============================================
 */
Route::post('admin', 'AdministratorController@login');
Route::post('customer/register', 'CustomerController@register');
Route::post('customer', 'CustomerController@login');
Route::post('reset_password', 'PasswordResetController@create');
Route::post('professional', 'ProfessionalController@login');
Route::post('professional/register', 'ProfessionalController@register');

/**
 * Logged routes
 */
Route::group(['middleware' => 'auth:api'], function () {

    /**
     * ============================================= Account =============================================
     */
    Route::post('/user/logout', 'UserController@logout')->middleware('role:administrator,customer,professional');
    Route::delete('/customer', 'CustomerController@delete')->middleware('role:customer');
    Route::get('/customer', 'CustomerController@show')->middleware(('role:customer'));
    Route::get('/customers', 'CustomerController@index')->middleware(('role:administrator'));
    Route::delete('/professional', 'ProfessionalController@delete')->middleware('role:professional');
    Route::get('/professional/{id}', 'ProfessionalController@show')->middleware('role:administrator, professional');

    /**
     * ============================================= Transport =============================================
     */
    Route::get('/transports', 'TransportController@index')->middleware('role:customer');

    /**
     * ============================================= Category =============================================
     */
    Route::get('/categories', 'CategoryController@index')->middleware('role:administrator, customer');

    /**
     * ============================================= Postal Code ==========================================
     */
    Route::get('/postal_codes', 'PostalCodeController@index')->middleware('role:administrator, professional');

    /**
     * ============================================= Option =============================================
     */
    Route::get('/options', 'OptionController@index')->middleware('role:administrator');

    /**
     * ============================================= Filter =============================================
     */
    Route::get('/filters', 'FilterController@index')->middleware('role:customer');

    /**
     * ============================================= Activity =============================================
     */
    Route::get('/activities', 'ActivityController@index')->middleware('role:administrator');
    Route::get('/activities/{id}', 'ActivityController@show')->middleware('role:administrator');
    Route::put('/activities/{id}', 'ActivityController@update')->middleware('role:administrator');
    Route::post('/activities', 'ActivityController@store')->middleware('role:administrator');
    Route::delete('/activities/{id}', 'ActivityController@delete')->middleware('role:administrator');

    // State
    Route::get('/activities/{id}/accept', 'ActivityController@accept')->middleware('role:administrator');
    Route::get('/activities/{id}/deny', 'ActivityController@deny')->middleware('role:administrator');
    Route::get('/activities/{id}/pend', 'ActivityController@pend')->middleware('role:administrator');


    /**
     * ============================================= Search =============================================
     */
    Route::post('/search', 'SearchController@search')->middleware('role:customer');
});
