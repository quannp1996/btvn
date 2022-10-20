<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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
/** @var Route $router */
try {
    $router->group([
        'namespace' => '\App\Http\Controllers',
        'domain' =>'seller1.local',
        'middleware' => ['ipseller1']
    ], function () use ($router) {
        $router->get('/save', 'JsonController@index');
        $router->get('/seller1/itemlist','JsonController@getProduct');
    });

    $router->group([
        'namespace' => '\App\Http\Controllers',
        'domain' => 'seller2.local',
        'middleware' => ['ipseller2']
    ], function () use ($router) {
        $router->get('/save', 'JsonController@index');
        $router->get('/seller2/itemlist', 'JsonController@getProduct');
    });

    Auth::routes();
    Route::post('/login', ['as' => 'auth.login', '\App\Http\Controllers\Auth\LoginController@login']);
    $router->group([
        'namespace' => '\App\Http\Controllers',
        'domain' => env('APP_DOMAIN'),
        'middleware' => ['auth']
    ], function () use ($router) {
        Route::get('/','HomeController@index')->name('home');
        Route::post('/purchase','HomeController@purchase')->name('purchase');
        Route::get('/listpurchase','HomeController@listpurchase')->name('listpurchase');
        Route::get('/recharge','HomeController@recharge')->name('recharge');
        Route::post('/saverecharge','HomeController@saverecharge')->name('saverecharge');
        Route::post('/delete','HomeController@delete')->name('delete');
    });
} catch (Exception $e) {
    dd($e->getMessage(), $e->getLine(), $e->getFile());
}

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
