<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('v1')->group(function () {

    // Auth APIs
    Route::prefix("auth")->controller(AuthController::class)->group(function () {
        // Auth (public)
        Route::post("/register", "register");
        Route::post("/login", "login");

        // Auth (protected)
        Route::middleware("auth:sanctum")->group(function () {
            Route::get("/me", function (Request $request) {
                return $request->user();
            });
            Route::post("/logout", "logout");
        });
    });

    // Sales APIs
    
});

