<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'StaticPagesController@home');
Route::get('/about', 'StaticPagesController@about');
Route::get('/help', 'StaticPagesController@help');