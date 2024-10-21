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
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Services\GuestUserService;



Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    return response()->json(['error' => 'Unauthorized'], 401);
});
//Route::get('/home',function(){return "welcome to our api";})->name('home');;
Route::middleware('web')->get('/home', function () {
    if (session('status') == 'success') {
        $guestUser = session('guestUser');
        $sessionDetails = session('sessionDetails');
        return "
            Guest User: {$guestUser['name']} <br>
            Session Start Time: {$sessionDetails['sessionStartTime']} <br>
            Session End Time: {$sessionDetails['sessionEndtTime']} <br>
            Meeting URL: <a href='{$sessionDetails['meetUrl']}'>{$sessionDetails['meetUrl']}</a> <br>
            Event ID: {$sessionDetails['eventId']}
        ";
    } else {
        return "Welcome to our API";
    }
})->name('home');



Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('blogs')->group(function () {
        Route::post('/', [BlogsController::class, 'create']);
        Route::get('/{id}/{language}', [BlogsController::class, 'index']);
        Route::put('/{id}', [BlogsController::class, 'update']);
        Route::get('/lastThree/{language}', [BlogsController::class, 'getLastThreeBlogs']);
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
        Route::post('/getCourseHaveTime', [CourcesController::class,'getCourseHaveTime']);
        Route::delete('/', [CourcesController::class, 'destroy']);
    });

    Route::prefix('courses_time')->group(function () {
        Route::post('/', [CourcesTimeController::class, 'create']);
        Route::get('/{userId}', [CourcesTimeController::class, 'index']);
        Route::get('/courseDays/{id}/{courseId}', [CourcesTimeController::class, 'getDaysByCourseId']);
        Route::get('/availableTimes/{course_id}/{sessionTimie}/{userId}', [CourcesTimeController::class, 'getAvailableTimes']);
        route::post('/timezone',[CourcesTimeController::class,'getAvailableTimeZone']);
        Route::post('/getAvailableTimeZoneForAdmin', [CourcesTimeController::class, 'getAvailableTimeZoneForAdmin']);
        Route::put('/{id}', [CourcesTimeController::class, 'update']);
        Route::post('/getAlltimes', [CourcesTimeController::class, 'getAlltimes']);
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
        Route::post('/getFreeLesson', [FreeLessonsController::class, 'index']);
        Route::put('/{id}', [FreeLessonsController::class, 'update']);
        Route::delete('/', [FreeLessonsController::class, 'destroy']);
    });
        Route::prefix('Testimonial')->group(function () {
        Route::post('/', [TestimonialController::class, 'create']);
        Route::post('/changeVisibility', [TestimonialController::class, 'changeVisibility']);
        Route::get('/', [TestimonialController::class, 'index']);
        Route::put('/{id}', [TestimonialController::class, 'update']);
        Route::get('/validTestimonial', [TestimonialController::class, 'validTestimonial']);
        Route::get('/getAllTestimonialsForAdmin', [TestimonialController::class, 'getAllTestimonialsForAdmin']);
        Route::delete('/', [TestimonialController::class, 'destroy']);
    });
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index']);    
        Route::get('/{id}', [UserController::class, 'show']); 
        Route::post('/signIn', [UserController::class, 'create']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::get('create_event',[FreeLessonsController::class,'createEvent']);
   
});

Route::post('register1', [RegisteredUserController::class,'store'])
->middleware('guest')
->name('register1');
Route::post('logIn', [RegisteredUserController::class,'login'])
->middleware('guest')
->name('logIn');
Route::post('logOut', [RegisteredUserController::class,'logout'])
->middleware('auth')
->name('logOut');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->middleware(['signed'])
        ->name('verification.verify');

Route::get('/verify-subscriber-email/{token}', [SubscribersController::class, 'verify']);

Route::get('/verify-guest-email/{token}/{courseId}/{sessionTimings}', [GuestUserService::class, 'verifyGuestUser']);

