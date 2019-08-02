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

Route::get('/', function () {
    return view('welcome');
});
Route::post('/weixin/valid','WeixinController@wxEvent');//接收消息推送
Route::get('weixin/valid','WeixinController@valid');
Route::get('/token','WeixinController@get_token');//获取access_token
