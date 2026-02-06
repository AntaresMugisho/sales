<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\ProductController;

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

    // Protected routes
    Route::middleware("auth:sanctum")->group(function () {
        
        // Product APIs
        Route::prefix("products")->controller(ProductController::class)->group(function () {
            Route::get("/", "index");
            Route::post("/", "store");
            Route::get("/{product}", "show");
            Route::put("/{product}", "update");
            Route::delete("/{product}", "destroy");
        });

        // Sale APIs
        Route::prefix("sales")->controller(SaleController::class)->group(function () {
            Route::get("/", "index");
            Route::post("/", "store");
            Route::get("/{sale}", "show");    
            Route::put("/{sale}", "update");  
            Route::delete("/{sale}", "destroy"); 
        });

        // Sync APIs
        Route::prefix("sync")->controller(SyncController::class)->group(function () {
            Route::post("/sales", "syncSales");
        });
    });
});

