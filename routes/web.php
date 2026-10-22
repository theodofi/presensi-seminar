<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardTemplateController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('scan'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/scan', [ScanController::class, 'index'])->name('scan');
    Route::post('/scan', [ScanController::class, 'store'])->name('scan.store');

    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::post('/sessions/reorder', [SessionController::class, 'reorder'])->name('sessions.reorder');
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::post('/sessions/{session}/mode', [SessionController::class, 'mode'])->name('sessions.mode');

    Route::get('/card-template', [CardTemplateController::class, 'edit'])->name('card.edit');
    Route::post('/card-template', [CardTemplateController::class, 'upload'])->name('card.upload');
    Route::put('/card-template', [CardTemplateController::class, 'update'])->name('card.update');

    Route::get('/participants/cards', [ParticipantController::class, 'cards'])->name('participants.cards');
    Route::post('/participants/import', [ParticipantController::class, 'import'])->name('participants.import');
    Route::resource('participants', ParticipantController::class)->except(['show']);

    Route::get('/report', [ReportController::class, 'index'])->name('report');
    Route::get('/report/export', [ReportController::class, 'export'])->name('report.export');
});
