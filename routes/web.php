<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SignatoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('documents.file');
    Route::post('documents/{document}/signatories', [SignatoryController::class, 'store'])->name('documents.signatories.store');
    Route::put('documents/{document}/signatories/reorder', [SignatoryController::class, 'reorder'])->name('documents.signatories.reorder');
    Route::put('signatories/{signatory}', [SignatoryController::class, 'update'])->name('signatories.update');
    Route::delete('signatories/{signatory}', [SignatoryController::class, 'destroy'])->name('signatories.destroy');
    Route::resource('documents', DocumentController::class);
});

require __DIR__.'/auth.php';
