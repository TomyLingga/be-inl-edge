<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('pmg', [App\Http\Controllers\Master\PmgController::class, 'index']);
Route::get('pmg/get/{id}', [App\Http\Controllers\Master\PmgController::class, 'show']);

Route::get('uraian-beban-prod', [App\Http\Controllers\Master\BebanProdUraianController::class, 'index']);
Route::get('uraian-beban-prod/get/{id}', [App\Http\Controllers\Master\BebanProdUraianController::class, 'show']);

Route::get('uraian-target-prod', [App\Http\Controllers\Master\TargetProdUraianController::class, 'index']);
Route::get('uraian-target-prod/get/{id}', [App\Http\Controllers\Master\TargetProdUraianController::class, 'show']);
//beban
Route::get('beban-prod', [App\Http\Controllers\CpoVs\BebanProdController::class, 'index']);
Route::get('beban-prod/get/{id}', [App\Http\Controllers\CpoVs\BebanProdController::class, 'show']);
Route::post('beban-prod/period', [App\Http\Controllers\CpoVs\BebanProdController::class, 'indexPeriod']);
//target
Route::get('target-prod', [App\Http\Controllers\CpoVs\TargetProdController::class, 'index']);
Route::get('target-prod/get/{id}', [App\Http\Controllers\CpoVs\TargetProdController::class, 'show']);
Route::post('target-prod/period', [App\Http\Controllers\CpoVs\TargetProdController::class, 'indexPeriod']);

Route::group(['middleware' => 'levelone.checker'], function () {
    //PMG
    Route::post('pmg/add', [App\Http\Controllers\Master\PmgController::class, 'store']);
    Route::post('pmg/update/{id}', [App\Http\Controllers\Master\PmgController::class, 'update']);
    //Beban Prod Uraian
    Route::post('uraian-beban-prod/add', [App\Http\Controllers\Master\BebanProdUraianController::class, 'store']);
    Route::post('uraian-beban-prod/update/{id}', [App\Http\Controllers\Master\BebanProdUraianController::class, 'update']);
    //Target Prod Uraian
    Route::post('uraian-target-prod/add', [App\Http\Controllers\Master\TargetProdUraianController::class, 'store']);
    Route::post('uraian-target-prod/update/{id}', [App\Http\Controllers\Master\TargetProdUraianController::class, 'update']);
    //beban
    Route::post('beban-prod/add', [App\Http\Controllers\CpoVs\BebanProdController::class, 'store']);
    Route::post('beban-prod/update/{id}', [App\Http\Controllers\CpoVs\BebanProdController::class, 'update']);
    //target
    Route::post('target-prod/add', [App\Http\Controllers\CpoVs\TargetProdController::class, 'store']);
    Route::post('target-prod/update/{id}', [App\Http\Controllers\CpoVs\TargetProdController::class, 'update']);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
