<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\Public;
use Illuminate\Support\Facades\Route;

/*
| Public content is read-only and returns published rows only.
| Admin routes live under /api/admin and require a Sanctum bearer token.
*/

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login')->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('me', [AuthController::class, 'me'])->name('auth.me');
});

Route::get('testimonials', [Public\TestimonialController::class, 'index'])->name('testimonials.index');
Route::get('faqs', [Public\FaqController::class, 'index'])->name('faqs.index');
Route::get('team-members', [Public\TeamMemberController::class, 'index'])->name('team-members.index');
Route::get('clients', [Public\ClientController::class, 'index'])->name('clients.index');
Route::get('properties', [Public\PropertyController::class, 'index'])->name('properties.index');
Route::get('properties/{slug}', [Public\PropertyController::class, 'show'])->name('properties.show');
Route::get('property-filters', Public\PropertyFilterController::class)->name('property-filters');

Route::post('inquiries', [InquiryController::class, 'store'])->middleware('throttle:inquiries')->name('inquiries.store');

Route::prefix('admin')->name('admin.')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('testimonials', Admin\TestimonialController::class);
    Route::apiResource('faqs', Admin\FaqController::class);
    Route::apiResource('team-members', Admin\TeamMemberController::class);
    Route::apiResource('clients', Admin\ClientController::class);
    Route::apiResource('properties', Admin\PropertyController::class);

    Route::scopeBindings()->prefix('properties/{property}/images')->name('properties.images.')->group(function () {
        Route::post('/', [Admin\PropertyImageController::class, 'store'])->name('store');
        Route::patch('{image}', [Admin\PropertyImageController::class, 'update'])->name('update');
        Route::delete('{image}', [Admin\PropertyImageController::class, 'destroy'])->name('destroy');
    });
});
