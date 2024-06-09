<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/create-symlink', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    if (!file_exists($link)) {
        symlink($target, $link);
        return "Symlink created successfully.";
    } else {
        return "Symlink already exists.";
    }
});


Route::get('/any-route', function () {
    Artisan::call('storage:link');
    return "Symlink created successfully.";
  });
