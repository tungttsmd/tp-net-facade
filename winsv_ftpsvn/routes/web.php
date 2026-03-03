<?php

use Illuminate\Support\Facades\Route;
use App\Events\MessageSent;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HostController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\HostTagController;
use App\Http\Controllers\TagController;

Route::prefix('api')->group(function () {
    Route::get('reverb-test', function () {
        $data = [
            'meta' => [
                'code' => 200,
                'message' => 'Success',
            ],
            'data' => [
                "api" => '/api/reverb-test',
            ],
        ];

        # Đưa array/object... về string json
        $message = $data;

        # Gọi Event MessageSent cho queue:worker xử lí
        event(new MessageSent($message));

        return response()->json("queued");
    })->name('reverb-test');
    Route::post('/sensor/temperatures/refresh', [SensorController::class, 'refresh']);
    Route::get('/sensor/temperatures/index', [SensorController::class, 'index']);
    Route::post('/dashboard/fetch', [DashboardController::class, 'fetch'])->name('dashboard.fetch');

    Route::post('command/{module}/{agent}/{command}', function ($module, $agent, $command) {

        $data = [
            'meta' => [
                'code' => 200,
                'message' => 'Success',
            ],
            'data' => [
                "api" => '/api/command/$module/$agent/$command',
            ],
        ];
        return Http::post("localhost:1882/api/command/$module/$agent/$command", $data);
    });

    Route::get('/host/names', [HostController::class, 'names']);
    Route::post('/host/{hostId}/rename', [HostController::class, 'rename']);

    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{name}', [TagController::class, 'update']);
    Route::delete('/tags/{name}', [TagController::class, 'destroy']);

    Route::get('/host/tags', [HostTagController::class, 'index']);
    Route::post('/host/{id}/tags', [HostTagController::class, 'save']);
    Route::get('/tags/master', [HostTagController::class, 'index']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/market', [DashboardController::class, 'market'])->name('market');
Route::get('/tags', fn() => view('tags.index'));
