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

Route::get('/', function () {
    return view('auth/login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::post('ajaxRequest', 'AjaxController@ajaxRequestPost')->name('ajaxRequest.post');
Route::post('ajaxRequestDetalles', 'AjaxController@getDetallesComprobante')->name('ajaxRequest.detalles');

Route::post('pdfController', 'PdfController@pdfGenerator')->name('pdfController.generator');
Route::get('pdfControllerDownload/{file_name}', 'PdfController@downloadPDF');