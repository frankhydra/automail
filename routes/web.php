<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SendingIdentityController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public Welcome Page
Route::get('/', function () {
    return view('welcome');
});

// Public webhook endpoints called by email providers to report bounces, complaints,
// and deliveries. Not session/CSRF-protected (the provider isn't logged in) - each
// verifies the request itself; see WebhookController and the CSRF exemption in
// bootstrap/app.php.
Route::post('/webhooks/brevo/{token}', [WebhookController::class, 'brevo'])->name('webhooks.brevo');
Route::post('/webhooks/resend', [WebhookController::class, 'resend'])->name('webhooks.resend');

// Public, signed unsubscribe links (one link = one recipient). GET only shows a confirmation page.
Route::middleware(['signed', 'throttle:30,1'])->group(function () {
    Route::get('/unsubscribe/{recipient}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
    Route::post('/unsubscribe/{recipient}', [UnsubscribeController::class, 'store'])->name('unsubscribe.store');
});

// Public, signed tracking routes (open pixel & click redirects)
Route::middleware('signed')->group(function () {
    Route::get('/track/open/{recipientId}', [TrackingController::class, 'trackOpen'])->name('track.open');
    Route::get('/track/click/{recipientId}', [TrackingController::class, 'trackClick'])->name('track.click');
});

// Public, signed link emailed to a sender address to prove ownership
Route::get('/sending-identities/verify/{token}', [SendingIdentityController::class, 'verify'])
    ->middleware('signed')
    ->name('sending-identities.verify');

// Public: opened from the team invitation email. GET is signed (only a genuine,
// unexpired emailed link works); the POST that completes signup for a brand new
// account is guarded by the invitation's own 48-character token instead.
Route::get('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->middleware('signed')
    ->name('invitations.accept');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'register'])
    ->name('invitations.register');

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

    // Contact Management Routes
    Route::resource('contacts', ContactController::class)->except(['show', 'create', 'store']);

    // Sending Identity Routes
    Route::get('/sending-identities', [SendingIdentityController::class, 'index'])->name('sending-identities.index');
    Route::post('/sending-identities', [SendingIdentityController::class, 'store'])->name('sending-identities.store');
    Route::post('/sending-identities/{id}/send-verification', [SendingIdentityController::class, 'sendVerification'])->name('sending-identities.send-verification');
    Route::post('/sending-identities/{id}/check-dns', [SendingIdentityController::class, 'checkDns'])->name('sending-identities.check-dns');
    Route::delete('/sending-identities/{id}', [SendingIdentityController::class, 'destroy'])->name('sending-identities.destroy');

    // Template Routes
    Route::resource('templates', TemplateController::class)->except(['show']);

    // Segment Routes
    Route::resource('segments', SegmentController::class)->except(['show']);

    // Team Routes
    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
    Route::post('/team/invitations/{invitationId}/resend', [TeamController::class, 'resend'])->name('team.invitations.resend');
    Route::delete('/team/invitations/{invitationId}', [TeamController::class, 'cancelInvite'])->name('team.invitations.cancel');
    Route::patch('/team/members/{memberId}/role', [TeamController::class, 'updateRole'])->name('team.members.role');
    Route::delete('/team/members/{memberId}', [TeamController::class, 'removeMember'])->name('team.members.remove');

    // API Token Routes
    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    // Billing Routes
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');

    // Campaign Routes
    Route::post('/campaigns/{id}/dispatch', [CampaignController::class, 'dispatch'])->name('campaigns.dispatch');
    Route::post('/campaigns/{id}/cancel', [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::resource('campaigns', CampaignController::class);
});

require __DIR__.'/auth.php';
