<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and will be assigned to
| the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group(function () {


    // Rutas de autenticación
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('checktoken', [AuthController::class, 'checkToken']);
    });

    // Rutas de usuarios
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::post('users/upload-photo', [UserController::class, 'uploadPhoto']);


    Route::post('users/{id}/ban', [UserController::class, 'banUser']);
    Route::post('users/{id}/change-role', [UserController::class, 'changeUserRole']);
    Route::get('admin/users', [UserController::class, 'indexAdmin']);



    // Rutas de productos
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{id}', [ProductController::class, 'show']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::get('user-products', [ProductController::class, 'getUserProducts']);




    // Rutas de categorías
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{id}', [CategoryController::class, 'show']);
    Route::put('categories/{id}', [CategoryController::class, 'update']);
    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    // Rutas de chats
    Route::get('chats', [ChatController::class, 'index']);
    Route::post('chats', [ChatController::class, 'store']);
    Route::get('chats/{id}', [ChatController::class, 'show']);
    Route::put('chats/{id}', [ChatController::class, 'update']);
    Route::delete('chats/{id}', [ChatController::class, 'destroy']);

    // Rutas de mensajes
    Route::get('messages', [MessageController::class, 'index']);
    Route::post('messages', [MessageController::class, 'store']);
    Route::get('messages/{id}', [MessageController::class, 'show']);
    Route::put('messages/{id}', [MessageController::class, 'update']);
    Route::delete('messages/{id}', [MessageController::class, 'destroy']);

    // Rutas de relaciones entre productos y categorías
    Route::get('product-categories', [ProductCategoryController::class, 'index']);
    Route::post('product-categories', [ProductCategoryController::class, 'store']);
    Route::get('product-categories/{id}', [ProductCategoryController::class, 'show']);
    Route::put('product-categories/{id}', [ProductCategoryController::class, 'update']);
    Route::delete('product-categories/{id}', [ProductCategoryController::class, 'destroy']);





});
