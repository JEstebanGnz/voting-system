<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Revolution\Google\Sheets\Facades\Sheets;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    $user = auth()->user();

    if ($user->role_id === 1) {

        return redirect()->route('votes.index.view');
    }

    return redirect()->route('elections.index.view');
})->middleware(['auth']);

/* >>>>>Roles routes <<<<<< */

//Get all roles
Route::get('/roles', [\App\Http\Controllers\Roles\RoleController::class, 'index'])->middleware(['auth', 'isAdmin'])->name('roles.index');
//Roles api
Route::resource('api/roles', \App\Http\Controllers\Roles\ApiRoleController::class, [
    'as' => 'api'
])->middleware('auth');

/* >>>>>User routes <<<<<< */

//Get all users
Route::inertia('/users', 'Users/Index')->middleware(['auth'])->name('users.index.view');
//users api
Route::resource('api/users', \App\Http\Controllers\Users\ApiUserController::class, [
    'as' => 'api'
])->middleware('auth');
//Update user role
Route::patch('/api/users/{user}/roles', [\App\Http\Controllers\Users\ApiUserController::class, 'updateUserRole'])->middleware('auth')->name('api.users.roles.update');
Route::get('/api/users/{user}/roles', [\App\Http\Controllers\Users\ApiUserController::class, 'getUserRole'])->middleware('auth')->name('api.users.roles.show');

/* >>>>>Roles routes <<<<<< */
Route::get('landing', function () {
    return Inertia::render('SuperTest');
})->name('landing');

////Auth routes
//Route::get('login', [\App\Http\Controllers\AuthController::class, 'redirectGoogleLogin'])->name('login');
//Route::get('/google/callback', [\App\Http\Controllers\AuthController::class, 'handleGoogleCallback']);

Route::inertia('/login', 'Custom/CustomLogin')->name('login');
/* >>>>>Elections routes <<<<<< */
Route::inertia('/elections', 'Elections/Index')->middleware(['auth', 'isAdmin'])->name('elections.index.view');
Route::resource('api/elections', \App\Http\Controllers\ElectionController::class, [
    'as' => 'api'
]);
Route::post('/api/elections/{election}/setActive', [\App\Http\Controllers\ElectionController::class, 'setActive'])->middleware(['auth', 'isAdmin'])->name('api.elections.setActive');
Route::post('/api/elections/{election}/deactivate', [\App\Http\Controllers\ElectionController::class, 'deactivate'])->middleware(['auth', 'isAdmin'])->name('api.elections.deactivate');
Route::get('/api/elections/{electionId}/boards', [\App\Http\Controllers\ElectionController::class, 'getBoards'])->middleware(['auth'])->name('api.elections.boards');
Route::get('/elections/active', [\App\Http\Controllers\ElectionController::class, 'getActive'])->middleware(['auth'])->name('elections.active');
Route::get('/elections/{election}/report', [\App\Http\Controllers\ElectionController::class, 'generateReport'])->middleware(['auth', 'isAdmin'])->name('elections.report');


/* >>>>>Boards routes <<<<<< */
Route::inertia('/elections/{electionId}/boards', 'Boards/Index')->middleware(['auth', 'isAdmin'])->name('boards.index.view');
Route::resource('api/boards', \App\Http\Controllers\BoardController::class, [
    'as' => 'api'
]);

/* >>>>>Votes routes <<<<<< */
Route::inertia('/votes', 'Votes/Index')->name('votes.index.view');
Route::resource('api/votes', \App\Http\Controllers\VoteController::class, [
    'as' => 'api'
]);
Route::get('/elections/{election}/{user}/', [\App\Http\Controllers\VoteController::class, 'isAbleToVote'])->middleware(['auth'])->name('votes.user.isAbleToVote');


Route::get('/insertAdmin', function () {

   \Illuminate\Support\Facades\DB::table('users')->insert(['name' => 'Admin', 'email' => 'desarrolladorg3@unibague.edu.co',
       'identification_number' => 12345,
       'role_id' => 2, 'has_payment' => 0,
       'password'=> Hash::make(12345)]);

});
