<?php

use App\Http\Controllers\MarcaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoriaController;
use Spatie\Permission\Models\Role;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

});

Route::apiResource('marcas', MarcaController::class);
Route::apiResource('productos', ProductoController::class);
Route::apiResource('ordenes', OrderController::class);

Route::put('ordenes/estado/{id}', [OrderController::class, 'gestionarEstado']);

//si solo usuarios con el role ADMIN, tendrán acceso a las rutas de marcas y categorías se pueden proteger de la forma siguiente:
    Route::middleware(['auth:api', 'role:ADMIN'])->group(function () {
    Route::apiResource('marcas',MarcaController::class);
    Route::apiResource('categorias',CategoriaController::class);    
});