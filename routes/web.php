<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HandoverController;
use App\Http\Controllers\Admin\KnowledgeItemController;
use App\Http\Controllers\Admin\UnitTypeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConciergeChatController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\VillaPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VillaPageController::class, 'index'])->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('unit-types', UnitTypeController::class)->except('show');
        Route::resource('knowledge-items', KnowledgeItemController::class)->except('show');

        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('handovers', [HandoverController::class, 'index'])->name('handovers.index');
        Route::get('handovers/{handover}', [HandoverController::class, 'show'])->name('handovers.show');
        Route::post('handovers/{handover}/reply', [HandoverController::class, 'reply'])->name('handovers.reply');
        Route::post('handovers/{handover}/resolve', [HandoverController::class, 'resolve'])->name('handovers.resolve');
    });
});

Route::prefix('{villaSlug}')->group(function () {
    Route::get('/', [VillaPageController::class, 'show'])->name('villa.show');
    Route::get('units', [VillaPageController::class, 'units'])->name('villa.units');
    Route::get('units/{unitSlug}', [VillaPageController::class, 'unit'])->name('villa.unit');
    Route::get('facilities', [VillaPageController::class, 'facilities'])->name('villa.facilities');
    Route::get('facilities/{facilityId}', [VillaPageController::class, 'facility'])->whereNumber('facilityId')->name('villa.facility');
    Route::get('info', [VillaPageController::class, 'info'])->name('villa.info');
    Route::get('staff', [VillaPageController::class, 'staff'])->name('villa.staff');
    Route::get('reservation', [VillaPageController::class, 'reservationScene'])->name('villa.reservation');

    Route::prefix('reservation')->name('reservation.')->middleware('throttle:20,1')->group(function () {
        Route::post('quote', [ReservationController::class, 'quote'])->name('quote');
        Route::post('/', [ReservationController::class, 'store'])->name('store');
    });

    Route::prefix('concierge')->name('concierge.')->group(function () {
        Route::post('start', [ConciergeChatController::class, 'start'])->middleware('throttle:concierge-start')->name('start');
        Route::post('message', [ConciergeChatController::class, 'message'])->middleware('throttle:concierge-message')->name('message');
        Route::get('history', [ConciergeChatController::class, 'history'])->middleware('throttle:concierge-history')->name('history');
    });
});
