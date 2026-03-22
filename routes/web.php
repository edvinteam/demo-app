<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::get('/features', [TaskController::class, 'features']);
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/search', [TaskController::class, 'search']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::get('/tasks/{task}/attachment', [TaskController::class, 'downloadAttachment']);
    Route::get('/tasks/export-pdf', [TaskController::class, 'exportPdf']);
});
