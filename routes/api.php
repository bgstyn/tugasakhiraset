<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RfidController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider or through bootstrap/app.php
| and will be assigned the "api" middleware group. Make something great!
|
*/

Route::get('/rfid/latest-scan', [RfidController::class, 'getLatestScan'])->name('api.rfid.latest');
Route::post('/raspi/frame', [RfidController::class, 'uploadFrame'])->name('api.raspi.frame');


Route::middleware('rfid.token')->group(function () {
    Route::post('/rfid-scan', [RfidController::class, 'scan'])->name('api.rfid.scan');
    Route::post('/rfid/register', [RfidController::class, 'register'])->name('api.rfid.register');
    Route::post('/rfid/validate', [RfidController::class, 'validateRfid'])->name('api.rfid.validate');
    Route::get('/rfid/asset', [RfidController::class, 'getAssetByRfid'])->name('api.rfid.asset');
    Route::get('/rfid/sync', [RfidController::class, 'sync'])->name('api.rfid.sync');
    Route::post('/rfid/sync', [RfidController::class, 'syncPost'])->name('api.rfid.sync-post');
});

