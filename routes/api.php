<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogsController;
use App\Http\Controllers\test;
use App\Http\Controllers\SubscribersController;
use App\Http\Controllers\CourcesController;
use App\Http\Controllers\CourcesTimeController;
use App\Http\Controllers\GuestUsersController;
use App\Http\Controllers\FreeLessonsController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\UserController;
use Spatie\GoogleCalendar\Event;
use App\Http\Middleware\VerifyCsrfToken;




Route::get('/',function(){return "welcome to our api";});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('blogs')->group(function () {
        Route::post('/', [BlogsController::class, 'create']);
        Route::post('/store', [BlogsController::class, 'store']);
        Route::get('/', [BlogsController::class, 'index']);
        Route::put('/{id}', [BlogsController::class, 'update']);
        Route::delete('/', [BlogsController::class, 'destroy']);
    });

    Route::prefix('subscribers')->group(function () {
        Route::post('/', [SubscribersController::class, 'create']);
        Route::get('/', [SubscribersController::class, 'index']);
        Route::put('/{id}', [SubscribersController::class, 'update']);
        Route::delete('/', [SubscribersController::class, 'destroy']);
    });

    Route::prefix('courses')->group(function () {
        Route::post('/', [CourcesController::class, 'create']);
        Route::get('/', [CourcesController::class, 'index']);
        Route::get('/courseById', [CourcesController::class, 'getCoursesByAge']);
        Route::put('/{id}', [CourcesController::class, 'update']);
        Route::delete('/', [CourcesController::class, 'destroy']);
    });

    Route::prefix('courses_time')->group(function () {
        Route::post('/', [CourcesTimeController::class, 'create']);
        Route::get('/{userId}', [CourcesTimeController::class, 'index']);
        Route::get('/courseDays/{id}/{courseId}', [CourcesTimeController::class, 'getDaysByCourseId']);
        Route::get('/availableTimes/{course_id}/{sessionTimie}/{userId}', [CourcesTimeController::class, 'getAvailableTimes']);
        Route::put('/{id}', [CourcesTimeController::class, 'update']);
        Route::delete('/', [CourcesTimeController::class, 'destroy']);
    });

    Route::prefix('guest_users')->group(function () {
        Route::post('/', [GuestUsersController::class, 'create']);
        Route::get('/', [GuestUsersController::class, 'index']);
        Route::put('/{id}', [GuestUsersController::class, 'update']);
        Route::delete('/', [GuestUsersController::class, 'destroy']);
    });

        Route::prefix('free_lessons')->group(function () {
        Route::post('/', [FreeLessonsController::class, 'create']);
        Route::post('/createSession', [FreeLessonsController::class,'createSession']);
        Route::get('/', [FreeLessonsController::class, 'index']);
        Route::put('/{id}', [FreeLessonsController::class, 'update']);
        Route::delete('/', [FreeLessonsController::class, 'destroy']);
    });
        Route::prefix('Testimonial')->group(function () {
        Route::post('/', [GuestUsersController::class, 'create']);
        Route::get('/', [GuestUsersController::class, 'index']);
        Route::put('/{id}', [GuestUsersController::class, 'update']);
        Route::delete('/', [GuestUsersController::class, 'destroy']);
    });
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index']);    
        Route::get('/{id}', [UserController::class, 'show']); 
        Route::post('/', [UserController::class, 'create']);    
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });
    Route::get('create_event',[FreeLessonsController::class,'createEvent']);
   
});

Route::get('/verify-subscriber-email/{token}', [SubscribersController::class, 'verify']);
Route::get('/verify-guest-email/{token}', [GuestUsersController::class, 'verify']);
       
