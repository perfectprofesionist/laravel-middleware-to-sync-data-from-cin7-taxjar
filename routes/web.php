<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('mail_with_attachment', function () {
    $date = date("Y-m-d");
    $data = [
        'body' => "Daily Log File",
        'attachment' => public_path('/storage/logs/laravel-'.$date.'.log')
    ];

    Mail::to('ritesh@yopmail.com')->send(new MailWithAttachment($data));

    dd('Mail send successfully.');
});
Route::get('/', function () {
    return view('welcome');
});
