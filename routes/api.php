<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExampleController;
use App\Http\Controllers\Api\AuthController;

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

Route::prefix('v1')->group(function () {
    // 健康检查
    Route::get('/health', function () {
        return api_success(['status' => 'ok', 'time' => now()], 'Service is running');
    });

    // ==================== 示例路由 ====================
    Route::prefix('examples')->middleware('throttle:api')->group(function () {
        Route::get('/', [ExampleController::class, 'index'])->name('examples.index');
        Route::get('/http', [ExampleController::class, 'httpExample'])->name('examples.http');
        Route::get('/cache', [ExampleController::class, 'cacheExample'])->name('examples.cache');
        Route::get('/queue', [ExampleController::class, 'queueExample'])->name('examples.queue');
        Route::get('/log', [ExampleController::class, 'logExample'])->name('examples.log');
        Route::post('/validation', [ExampleController::class, 'validationExample'])->name('examples.validation');
        Route::get('/exception', [ExampleController::class, 'exceptionExample'])->name('examples.exception');
        Route::get('/helper', [ExampleController::class, 'helperExample'])->name('examples.helper');
        Route::post('/full', [ExampleController::class, 'fullExample'])->name('examples.full');
    });

    // ==================== 认证路由 ====================
    Route::prefix('auth')->group(function () {
        // 无需认证的路由(应用登录限流)
        Route::middleware('throttle:auth')->group(function () {
            Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
            Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
        });

        // 需要认证的路由(应用通用 API 限流)
        Route::middleware(['jwt.auth', 'throttle:api'])->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        });
    });

    // ==================== 你的业务路由 ====================
    // 需要认证的业务路由(应用通用 API 限流)
    Route::middleware(['jwt.auth', 'throttle:api'])->group(function () {
        // 在这里添加你的业务路由
        // Route::apiResource('users', UserController::class);
        // Route::get('/profile', [ProfileController::class, 'show']);
    });

    // 不需要认证的业务路由(应用全局限流)
    // Route::middleware('throttle:global')->group(function () {
    //     Route::get('/posts', [PostController::class, 'index']);
    // });
});
