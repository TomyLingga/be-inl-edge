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

Route::get('supplier', [App\Http\Controllers\Partner\SupplierController::class, 'index']);
Route::get('supplier/get/{id}', [App\Http\Controllers\Partner\SupplierController::class, 'show']);

Route::get('source-cpo', [App\Http\Controllers\IncomingCpo\SourcingIncomingCpoController::class, 'index']);
Route::get('source-cpo/get/{id}', [App\Http\Controllers\IncomingCpo\SourcingIncomingCpoController::class, 'show']);

Route::get('target-income-cpo', [App\Http\Controllers\IncomingCpo\TargetIncomingCpoController::class, 'index']);
Route::get('target-income-cpo/get/{id}', [App\Http\Controllers\IncomingCpo\TargetIncomingCpoController::class, 'show']);

Route::get('matauang', [App\Http\Controllers\Kurs\MataUangController::class, 'index']);
Route::get('matauang/get/{id}', [App\Http\Controllers\Kurs\MataUangController::class, 'show']);

Route::get('kategori-cashflowmov', [App\Http\Controllers\CashFlowMov\KategoriCashFlowMovController::class, 'index']);
Route::get('kategori-cashflowmov/get/{id}', [App\Http\Controllers\CashFlowMov\KategoriCashFlowMovController::class, 'show']);

Route::get('kategori-cashflowschedlue', [App\Http\Controllers\CashFlowSchedule\KategoriCashFlowScheduleController::class, 'index']);
Route::get('kategori-cashflowschedlue/get/{id}', [App\Http\Controllers\CashFlowSchedule\KategoriCashFlowScheduleController::class, 'show']);

Route::get('kategori-profitability', [App\Http\Controllers\Profitability\KategoriProfitablityController::class, 'index']);
Route::get('kategori-profitability/get/{id}', [App\Http\Controllers\Profitability\KategoriProfitablityController::class, 'show']);

Route::get('profitability', [App\Http\Controllers\Profitability\ProfitablityController::class, 'index']);
Route::get('profitability/get/{id}', [App\Http\Controllers\Profitability\ProfitablityController::class, 'show']);
Route::post('profitability/period', [App\Http\Controllers\Profitability\ProfitablityController::class, 'indexPeriod']);

Route::get('income-cpo', [App\Http\Controllers\IncomingCpo\IncomingCpoController::class, 'index']);
Route::get('income-cpo/get/{id}', [App\Http\Controllers\IncomingCpo\IncomingCpoController::class, 'show']);
Route::post('income-cpo/period', [App\Http\Controllers\IncomingCpo\IncomingCpoController::class, 'indexPeriod']);

Route::get('paystatus-cashflowschedlue', [App\Http\Controllers\CashFlowSchedule\PayStatusCashFlowScheduleController::class, 'index']);
Route::get('paystatus-cashflowschedlue/get/{id}', [App\Http\Controllers\CashFlowSchedule\PayStatusCashFlowScheduleController::class, 'show']);
//beban
Route::get('cashflowschedlue', [App\Http\Controllers\CashFlowSchedule\CashFlowScheduleController::class, 'index']);
Route::get('cashflowschedlue/get/{id}', [App\Http\Controllers\CashFlowSchedule\CashFlowScheduleController::class, 'show']);
Route::post('cashflowschedlue/period', [App\Http\Controllers\CashFlowSchedule\CashFlowScheduleController::class, 'indexPeriod']);

Route::get('jenis-laporan-prod', [App\Http\Controllers\LaporanProduksi\JenisLaporanProduksiController::class, 'index']);
Route::get('jenis-laporan-prod/get/{id}', [App\Http\Controllers\LaporanProduksi\JenisLaporanProduksiController::class, 'show']);

Route::get('laporan-prod', [App\Http\Controllers\LaporanProduksi\LaporanProduksiController::class, 'index']);
Route::get('laporan-prod/get/{id}', [App\Http\Controllers\LaporanProduksi\LaporanProduksiController::class, 'show']);
Route::post('laporan-prod/period', [App\Http\Controllers\LaporanProduksi\LaporanProduksiController::class, 'indexPeriod']);

Route::get('jenis-laporan-material', [App\Http\Controllers\LaporanMaterial\JenisLaporanMaterialController::class, 'index']);
Route::get('jenis-laporan-material/get/{id}', [App\Http\Controllers\LaporanMaterial\JenisLaporanMaterialController::class, 'show']);

Route::get('norma-material', [App\Http\Controllers\LaporanMaterial\NormaMaterialController::class, 'index']);
Route::get('norma-material/get/{id}', [App\Http\Controllers\LaporanMaterial\NormaMaterialController::class, 'show']);

Route::get('laporan-material', [App\Http\Controllers\LaporanMaterial\LaporanMaterialController::class, 'index']);
Route::get('laporan-material/get/{id}', [App\Http\Controllers\LaporanMaterial\LaporanMaterialController::class, 'show']);
Route::post('laporan-material/period', [App\Http\Controllers\LaporanMaterial\LaporanMaterialController::class, 'indexPeriod']);

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
//cashflowmovement
Route::get('cashflowmov', [App\Http\Controllers\CashFlowMov\CashFlowMovController::class, 'index']);
Route::get('cashflowmov/get/{id}', [App\Http\Controllers\CashFlowMov\CashFlowMovController::class, 'show']);
Route::post('cashflowmov/period', [App\Http\Controllers\CashFlowMov\CashFlowMovController::class, 'indexPeriod']);
//target
Route::get('cpo-kpbn', [App\Http\Controllers\CPO\CpoKpbnController::class, 'index']);
Route::get('cpo-kpbn/get/{id}', [App\Http\Controllers\CPO\CpoKpbnController::class, 'show']);
Route::post('cpo-kpbn/period', [App\Http\Controllers\CPO\CpoKpbnController::class, 'indexPeriod']);
//kurs
Route::get('kurs', [App\Http\Controllers\Kurs\KursController::class, 'index']);
Route::get('kurs/get/{id}', [App\Http\Controllers\Kurs\KursController::class, 'show']);
Route::post('kurs/period', [App\Http\Controllers\Kurs\KursController::class, 'indexPeriod']);
//outstanding cpo
Route::get('outstanding-cpo', [App\Http\Controllers\Outstanding\OutstandingCpoController::class, 'index']);
Route::get('outstanding-cpo/get/{id}', [App\Http\Controllers\Outstanding\OutstandingCpoController::class, 'show']);
Route::get('outstanding-cpo/period', [App\Http\Controllers\Outstanding\OutstandingCpoController::class, 'indexPeriod']);
//saldo pe
Route::get('saldope', [App\Http\Controllers\SaldoPe\SaldoPeController::class, 'index']);
Route::get('saldope/get/{id}', [App\Http\Controllers\SaldoPe\SaldoPeController::class, 'show']);
Route::post('saldope/period', [App\Http\Controllers\SaldoPe\SaldoPeController::class, 'indexPeriod']);

Route::group(['middleware' => 'levelone.checker'], function () {
    //PMG
    Route::post('pmg/add', [App\Http\Controllers\Master\PmgController::class, 'store']);
    Route::post('pmg/update/{id}', [App\Http\Controllers\Master\PmgController::class, 'update']);
    //supplier
    Route::post('supplier/add', [App\Http\Controllers\Partner\SupplierController::class, 'store']);
    Route::post('supplier/update/{id}', [App\Http\Controllers\Partner\SupplierController::class, 'update']);
    //source cpo
    Route::post('source-cpo/add', [App\Http\Controllers\IncomingCpo\SourcingIncomingCpoController::class, 'store']);
    Route::post('source-cpo/update/{id}', [App\Http\Controllers\IncomingCpo\SourcingIncomingCpoController::class, 'update']);
    //target incoming cpo
    Route::post('target-income-cpo/add', [App\Http\Controllers\IncomingCpo\TargetIncomingCpoController::class, 'store']);
    Route::post('target-income-cpo/update/{id}', [App\Http\Controllers\IncomingCpo\TargetIncomingCpoController::class, 'update']);
    //incoming cpo
    Route::post('income-cpo/add', [App\Http\Controllers\IncomingCpo\IncomingCpoController::class, 'store']);
    Route::post('income-cpo/update/{id}', [App\Http\Controllers\IncomingCpo\IncomingCpoController::class, 'update']);
    //matauang
    Route::post('matauang/add', [App\Http\Controllers\Kurs\MataUangController::class, 'store']);
    Route::post('matauang/update/{id}', [App\Http\Controllers\Kurs\MataUangController::class, 'update']);
    //Kategori Cash flow movement
    Route::post('kategori-cashflowmov/add', [App\Http\Controllers\CashFlowMov\KategoriCashFlowMovController::class, 'store']);
    Route::post('kategori-cashflowmov/update/{id}', [App\Http\Controllers\CashFlowMov\KategoriCashFlowMovController::class, 'update']);
    // Cash flow movement
    Route::post('cashflowmov/add', [App\Http\Controllers\CashFlowMov\CashFlowMovController::class, 'store']);
    Route::post('cashflowmov/update/{id}', [App\Http\Controllers\CashFlowMov\CashFlowMovController::class, 'update']);
    //Kategori Profitability
    Route::post('kategori-profitability/add', [App\Http\Controllers\Profitability\KategoriProfitablityController::class, 'store']);
    Route::post('kategori-profitability/update/{id}', [App\Http\Controllers\Profitability\KategoriProfitablityController::class, 'update']);
    // Profitability
    Route::post('profitability/add', [App\Http\Controllers\Profitability\ProfitablityController::class, 'store']);
    Route::post('profitability/update/{id}', [App\Http\Controllers\Profitability\ProfitablityController::class, 'update']);
    //Kategori Cash flow schedule
    Route::post('kategori-cashflowschedlue/add', [App\Http\Controllers\CashFlowSchedule\KategoriCashFlowScheduleController::class, 'store']);
    Route::post('kategori-cashflowschedlue/update/{id}', [App\Http\Controllers\CashFlowSchedule\KategoriCashFlowScheduleController::class, 'update']);
    // Pay Status Cash flow schedule
    Route::post('paystatus-cashflowschedlue/add', [App\Http\Controllers\CashFlowSchedule\PayStatusCashFlowScheduleController::class, 'store']);
    Route::post('paystatus-cashflowschedlue/update/{id}', [App\Http\Controllers\CashFlowSchedule\PayStatusCashFlowScheduleController::class, 'update']);
    // Cash flow schedule
    Route::post('cashflowschedlue/add', [App\Http\Controllers\CashFlowSchedule\CashFlowScheduleController::class, 'store']);
    Route::post('cashflowschedlue/update/{id}', [App\Http\Controllers\CashFlowSchedule\CashFlowScheduleController::class, 'update']);
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
    //jenis laporan prod
    Route::post('jenis-laporan-prod/add', [App\Http\Controllers\LaporanProduksi\JenisLaporanProduksiController::class, 'store']);
    Route::post('jenis-laporan-prod/update/{id}', [App\Http\Controllers\LaporanProduksi\JenisLaporanProduksiController::class, 'update']);
    //laporan prod
    Route::post('laporan-prod/add', [App\Http\Controllers\LaporanProduksi\LaporanProduksiController::class, 'store']);
    Route::post('laporan-prod/update/{id}', [App\Http\Controllers\LaporanProduksi\LaporanProduksiController::class, 'update']);
    //jenis laporan material
    Route::post('jenis-laporan-material/add', [App\Http\Controllers\LaporanMaterial\JenisLaporanMaterialController::class, 'store']);
    Route::post('jenis-laporan-material/update/{id}', [App\Http\Controllers\LaporanMaterial\JenisLaporanMaterialController::class, 'update']);
    //norma material
    Route::post('norma-material/add', [App\Http\Controllers\LaporanMaterial\NormaMaterialController::class, 'store']);
    Route::post('norma-material/update/{id}', [App\Http\Controllers\LaporanMaterial\NormaMaterialController::class, 'update']);
    //laporan material
    Route::post('laporan-material/add', [App\Http\Controllers\LaporanMaterial\LaporanMaterialController::class, 'store']);
    Route::post('laporan-material/update/{id}', [App\Http\Controllers\LaporanMaterial\LaporanMaterialController::class, 'update']);
    //cpo Kpbn
    Route::post('cpo-kpbn/add', [App\Http\Controllers\CPO\CpoKpbnController::class, 'store']);
    Route::post('cpo-kpbn/update/{id}', [App\Http\Controllers\CPO\CpoKpbnController::class, 'update']);
    //kurs
    Route::post('kurs/add', [App\Http\Controllers\Kurs\KursController::class, 'store']);
    Route::post('kurs/update/{id}', [App\Http\Controllers\Kurs\KursController::class, 'update']);
    //outstanding cpo
    Route::post('outstanding-cpo/add', [App\Http\Controllers\Outstanding\OutstandingCpoController::class, 'store']);
    Route::post('outstanding-cpo/update/{id}', [App\Http\Controllers\Outstanding\OutstandingCpoController::class, 'update']);
    //saldo pe
    Route::post('saldope/add', [App\Http\Controllers\SaldoPe\SaldoPeController::class, 'store']);
    Route::post('saldope/update/{id}', [App\Http\Controllers\SaldoPe\SaldoPeController::class, 'update']);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
