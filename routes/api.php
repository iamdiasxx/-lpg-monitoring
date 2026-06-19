<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlokasiController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ExportController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register-user', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::get('/alokasi-resources', [AlokasiController::class, 'getResources']);
Route::post('/alokasi-store', [AlokasiController::class, 'store']);
Route::get('/operator-tasks', [AlokasiController::class, 'getOperatorTask']);
Route::get('/customer-notif/{id_user}', [AlokasiController::class, 'getCustomerNotification']);
Route::get('/admin-monitoring', [AlokasiController::class, 'getGlobalMonitoring']);

// Route untuk mengambil data Truk dan Supir (untuk pilihan di Modal)
Route::get('/fleet-resources', [AlokasiController::class, 'getFleetResources']);
// Route untuk menyimpan transaksi pengambilan SPBE
Route::post('/transaksi-spbe-store', [AlokasiController::class, 'storeTransaksiSpbe']);
Route::get('/monitoring-detail/{id}', [AlokasiController::class, 'getMonitoringDetail']);
Route::get('/tracking-detail/{id}', [AlokasiController::class, 'getTrackingDetail']);
Route::post('/start-distribution', [AlokasiController::class, 'startDistribution']);
Route::get('/personel', [AlokasiController::class, 'getPersonel']);
Route::post('/personel-store', [AlokasiController::class, 'storePersonel']);

Route::get('/dispatch-pending', [AlokasiController::class, 'getDispatchPending']);
Route::post('/dispatch-assign', [AlokasiController::class, 'storeDispatchAssignment']);

Route::get('/master-all', [MasterController::class, 'getAllMasterData']);
Route::post('/master/karyawan', [MasterController::class, 'storeKaryawan']);
Route::post('/master/truk', [MasterController::class, 'storeTruk']);
Route::post('/master/spbe', [MasterController::class, 'storeSpbe']);
Route::post('/master/{type}/save', [MasterController::class, 'storeOrUpdate']);
Route::delete('/master/{type}/{id}', [MasterController::class, 'destroy']);

Route::get('/audit-logs', [MasterController::class, 'getAuditLogs']);
Route::post('/pangkalan-konfirmasi', [AlokasiController::class, 'storeKonfirmasiPangkalan']);
Route::get('/customer-history/{id_user}', [AlokasiController::class, 'getCustomerHistory']);
Route::get('/operator-dispatch-history', [AlokasiController::class, 'getOperatorDispatchHistory']);

Route::get('/asset-summary', [MasterController::class, 'getAssetSummary']);

// Route untuk Update Stok Per Lantai
Route::post('/stock/update-floor', [AlokasiController::class, 'updateFloorStock']);

// Route untuk Pemindahan Antar Lantai
Route::post('/stock/move-floor', [AlokasiController::class, 'moveFloorStock']);

// Tambahan: API untuk mengambil data Mapping di Dashboard Manager
Route::get('/inventory-mapping', [MasterController::class, 'getInventoryMapping']);

Route::post('/stock/transfer', [AlokasiController::class, 'transferStock']);
Route::get('/pangkalan-dashboard/{id_user}', [AlokasiController::class, 'getPangkalanDashboard']);

Route::post('/truck/return', [AlokasiController::class, 'unloadTruck']);
Route::post('/truck/confirm-return', [AlokasiController::class, 'confirmTruckReturn']);

Route::get('/alokasi-existing', [AlokasiController::class, 'getExistingAllocations']);
Route::get('/admin-dashboard-extra', [MasterController::class, 'getDashboardExtra']);

Route::get('/users', [MasterController::class, 'getUsers']);
Route::post('/users/save', [MasterController::class, 'saveUser']);
Route::delete('/users/{id}', [MasterController::class, 'deleteUser']);

Route::get('/stock/audit-data', [AlokasiController::class, 'getSystemStockAudit']);
Route::post('/stock/reconcile', [AlokasiController::class, 'submitReconciliation']);

Route::get('/export/alokasi-pdf/{id}', [ExportController::class, 'exportAlokasiPDF']);
Route::get('/export/audit-excel', [ExportController::class, 'exportAuditExcel']);
Route::get('/export/rekap-excel', [ExportController::class, 'exportRekapExcel']);

Route::post('/pangkalan-reject', [AlokasiController::class, 'rejectDistribution']);
Route::post('/pangkalan/update-usage', [AlokasiController::class, 'updateUsagePangkalan']);
Route::get('/pangkalan/stock-movement/{id_user}', [AlokasiController::class, 'getPangkalanStockMovement']);


Route::post('/user/update-password', [AuthController::class, 'updatePassword']);
Route::get('/operator-spbe-history', [AlokasiController::class, 'getSpbeHistory']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});