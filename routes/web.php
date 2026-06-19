<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rute ini berfungsi sebagai 'penerjemah' agar Laravel yang mengirimkan foto, bukan Windows
Route::get('/display-bukti/{filename}', function ($filename) {
    $path = 'bukti_penerimaan/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $file = Storage::disk('public')->get($path);
    $type = Storage::disk('public')->mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

// Perbaikan: Tambahkan pengecualian agar tidak menangkap file asset/PWA
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api|storage|build|assets|manifest\.json|sw\.js|dev-sw\.js).*$');