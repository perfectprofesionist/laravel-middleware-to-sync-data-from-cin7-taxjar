<?php

use App\Http\Controllers\TestController;
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
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('test',[TestController::class, 'test']);
Route::get('test_1',[TestController::class, 'test_1']);
Route::post('test_create_customer',[TestController::class, 'test_create_customer']);
route::post('delete_order/{id}',[TestController::class,'delete_order']);

Route::post('create_user',[TestController::class, 'create_user']);
Route::group(['namespace' => 'App\Http\Controllers'], function(){
    // Route::get('/createtaxjarorder', "TaxjarController@createtaxjarorder")->name('createtaxjarorder');
    // Route::get('/createtaxjarcustomer', "TaxjarController@createtaxjarcustomer")->name('createtaxjarcustomer');
    // Route::get('/gettaxjarcustomer', "TaxjarController@gettaxjarcustomer")->name('gettaxjarcustomer');
});

