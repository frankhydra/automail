<?php

use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\ContactApiController;
use App\Http\Controllers\Api\SendingIdentityApiController;
use App\Http\Controllers\Api\TemplateApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (versioned: /api/v1/...)
|--------------------------------------------------------------------------
| Every route here requires a Sanctum personal access token (an
| "Authorization: Bearer <token>" header). Get one from Settings -> API
| Access in the app. All data is scoped to the token owner's organization -
| there is no way to reach another organization's data through this API.
|
| This is a read-only API for now (GET only). Write endpoints are a planned
| future addition, not built in this milestone.
*/
Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/contacts', [ContactApiController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{id}', [ContactApiController::class, 'show'])->name('contacts.show');
    Route::get('/lists', [ContactApiController::class, 'lists'])->name('lists.index');

    Route::get('/campaigns', [CampaignApiController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/{id}', [CampaignApiController::class, 'show'])->name('campaigns.show');

    Route::get('/templates', [TemplateApiController::class, 'index'])->name('templates.index');
    Route::get('/templates/{id}', [TemplateApiController::class, 'show'])->name('templates.show');

    Route::get('/sending-identities', [SendingIdentityApiController::class, 'index'])->name('sending-identities.index');

    Route::get('/analytics', [AnalyticsApiController::class, 'index'])->name('analytics.index');
});
