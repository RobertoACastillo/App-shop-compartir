<?php

use App\Http\Controllers\MarcaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
//creacion de rutas para API
Route::apiResource('marcas',MarcaController::class);
Route::apiResource('productos', ProductoController::class);
Route::apiResource('ordenes', OrderController::class);
//ruta especifica para acceder a la funcion de gestionarEstado
Route::put('ordenes/estado/{id}',[OrderController::class,'gestionarEstado']);


