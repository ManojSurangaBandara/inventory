<?php

use App\Http\Controllers\Api\ItemRequestApiController;
use App\Http\Middleware\VerifyApiToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for External Integrations (Workshop Management System)
|--------------------------------------------------------------------------
*/
Route::middleware([VerifyApiToken::class])->prefix('v1')->group(function () {
    Route::post('/item-requests', [ItemRequestApiController::class, 'store']);
});
