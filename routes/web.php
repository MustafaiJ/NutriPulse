<?php

use App\Http\Controllers\BloodSugarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DietPlanController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FoodEntryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TravelPeriodController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/insight', [DashboardController::class, 'insight'])
        ->middleware('role:admin')
        ->name('dashboard.insight');

    Route::get('/export/data.csv', [ExportController::class, 'csv'])->name('export.csv');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    /* Food log — patient can write, dietitian can read + comment */
    Route::get('/food', [FoodEntryController::class, 'index'])->name('food.index');
    Route::get('/food/create', [FoodEntryController::class, 'create'])->middleware('role:admin')->name('food.create');
    Route::post('/food', [FoodEntryController::class, 'store'])->middleware('role:admin')->name('food.store');
    Route::get('/food/{entry}/edit', [FoodEntryController::class, 'edit'])->middleware('role:admin')->name('food.edit');
    Route::patch('/food/{entry}', [FoodEntryController::class, 'update'])->middleware('role:admin')->name('food.update');
    Route::delete('/food/{entry}', [FoodEntryController::class, 'destroy'])->middleware('role:admin')->name('food.destroy');
    Route::post('/food/{entry}/comment', [FoodEntryController::class, 'comment'])->middleware('role:dietitian')->name('food.comment');

    /* Blood sugar — patient can write, dietitian can read */
    Route::get('/blood-sugar', [BloodSugarController::class, 'index'])->name('blood-sugar.index');
    Route::post('/blood-sugar', [BloodSugarController::class, 'store'])->middleware('role:admin')->name('blood-sugar.store');
    Route::delete('/blood-sugar/{reading}', [BloodSugarController::class, 'destroy'])->middleware('role:admin')->name('blood-sugar.destroy');

    /* Gym log — admin only (private) */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/workouts', [WorkoutController::class, 'index'])->name('workouts.index');
        Route::post('/workouts', [WorkoutController::class, 'store'])->name('workouts.store');
        Route::get('/workouts/{workout}/edit', [WorkoutController::class, 'edit'])->name('workouts.edit');
        Route::patch('/workouts/{workout}', [WorkoutController::class, 'update'])->name('workouts.update');
        Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->name('workouts.destroy');
    });

    /* Diet plans — dietitian writes, admin reads */
    Route::get('/diet-plans', [DietPlanController::class, 'index'])->name('diet-plans.index');
    Route::middleware(['role:dietitian'])->group(function () {
        Route::get('/diet-plans/create', [DietPlanController::class, 'create'])->name('diet-plans.create');
        Route::post('/diet-plans', [DietPlanController::class, 'store'])->name('diet-plans.store');
        Route::get('/diet-plans/{plan}/edit', [DietPlanController::class, 'edit'])->name('diet-plans.edit');
        Route::patch('/diet-plans/{plan}', [DietPlanController::class, 'update'])->name('diet-plans.update');
        Route::delete('/diet-plans/{plan}', [DietPlanController::class, 'destroy'])->name('diet-plans.destroy');
    });

    /* Travel mode — admin marks, dietitian reads */
    Route::get('/travel', [TravelPeriodController::class, 'index'])->name('travel.index');
    Route::post('/travel', [TravelPeriodController::class, 'store'])->middleware('role:admin')->name('travel.store');
    Route::delete('/travel/{period}', [TravelPeriodController::class, 'destroy'])->middleware('role:admin')->name('travel.destroy');

    /* User management — admin only */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
