<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [AuthController::class, 'login']);

Route::get('validateToken', [AuthController::class, 'validateToken']);
Route::post('recoverPassword', [UserController::class, 'passwordRecovery']);
Route::post('updatePassword', [UserController::class, 'updatePassword']);


Route::get('validateToken', [AuthController::class, 'validateToken']);

Route::prefix('client')->group(function(){
    Route::post('create', [ClientController::class, 'create']);
    Route::get('get-client-zodiac-sing', [ClientController::class, 'getClientZodiacSing']);
    Route::get('citys', [ClientController::class, 'getCitys']);
});

Route::prefix('product')->group(function(){
    Route::get('show', [ProductController::class, 'show']);
});

Route::prefix('sale')->group(function(){
    Route::get('search', [SaleController::class, 'search']);
    Route::get('verify-payment/{id}', [SaleController::class, 'verifyPayment']);
    Route::post('create', [SaleController::class, 'create']);
});

Route::prefix('setting')->group(function(){
    Route::get('/', [SettingController::class, 'search']);   
});

Route::post('logout', [AuthController::class, 'logout']);

Route::middleware('jwt')->group(function(){

    Route::middleware(AdminMiddleware::class)->group(function() {
        // Middleware do admin
    });

    Route::prefix('generated')->group(function(){
        Route::get('search', [ReportController::class, 'getGeneratedReports']);
    });

    Route::prefix('user')->group(function(){
        Route::get('all', [UserController::class, 'all']);
        Route::get('search', [UserController::class, 'search']);
        Route::get('cards', [UserController::class, 'cards']);
        Route::get('me', [UserController::class, 'getUser']);
        Route::post('create', [UserController::class, 'create']);
        Route::patch('{id}', [UserController::class, 'update']);
        Route::post('block/{id}', [UserController::class, 'userBlock']);
    });

    Route::prefix('product')->group(function(){
        Route::get('search', [ProductController::class, 'search']);
        Route::post('create', [ProductController::class, 'create']);
        Route::patch('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'delete']);
    });

    Route::prefix('sale')->group(function(){
        Route::get('{id}', [SaleController::class, 'getById']);
    });

    Route::prefix('client')->group(function(){
        Route::get('search', [ClientController::class, 'search']);
        Route::get('export', [ClientController::class, 'export']);
        Route::post('generate', [ClientController::class, 'generate']);        
        Route::patch('{id}', [ClientController::class, 'update']);
        Route::delete('{id}', [ClientController::class, 'delete']);
    });

    Route::prefix('setting')->group(function(){
        Route::patch('/', [SettingController::class, 'update']);        
    });
});
