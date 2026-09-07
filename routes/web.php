<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendingIdentityController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard Route
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Contact Import Routes
    Route::get('/contacts/import', [ContactImportController::class, 'show'])->name('contacts.import.show');
    Route::post('/contacts/import', [ContactImportController::class, 'store'])->name('contacts.import.store');

    // Sending Identity Routes
    Route::get('/sending-identities', [SendingIdentityController::class, 'index'])->name('sending-identities.index');
    Route::post('/sending-identities', [SendingIdentityController::class, 'store'])->name('sending-identities.store');
    Route::get('/sending-identities/verify/{token}', [SendingIdentityController::class, 'verify'])->name('sending-identities.verify');
    Route::delete('/sending-identities/{id}', [SendingIdentityController::class, 'destroy'])->name('sending-identities.destroy');

    // Template Routes
    Route::resource('templates', TemplateController::class)->except(['show']);

    // Campaign Routes
    Route::post('/campaigns/{id}/dispatch', [CampaignController::class, 'dispatch'])->name('campaigns.dispatch');
    Route::resource('campaigns', CampaignController::class);
});

require __DIR__.'/auth.php';