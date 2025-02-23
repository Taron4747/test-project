<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/upload', [ImportController::class, 'showUploadForm'])->name('upload.form');
Route::post('/upload', [ImportController::class, 'handleUpload'])->name('upload.handle');