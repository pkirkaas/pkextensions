<?php

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

Auth::routes();

Route::group(['middleware' => ['web']], function () {
  Route::any('/', ['as'=>'home', 'uses'=>'IndexController@index']);
  Route::any('index/contact', ['as'=>'index_contact', 'uses'=>'IndexController@contact']);
  Route::any('index/about', ['as'=>'index_about', 'uses'=>'IndexController@about']);

  Route::any('auth/login', ['as'=>'auth_login', 'uses'=>'Auth\LoginController@login']);
  Route::any('user/editprofile', ['middleware'=> 'auth',
     'as' => 'user_editprofile', 'uses'=>'UserController@editprofile']);
  Route::any('auth/logout', ['as' => 'auth_logout', 
           function() {
             Auth::logout();
             return redirect()->route('home');
           }
        ]);







});


