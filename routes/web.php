<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
Route::get('/users/suitableToAdd', [\App\Http\Controllers\Users\ApiUserController::class, 'getSuitableUsersToAdd'])->middleware('auth')->name('users.suitableToAdd');
Route::get('/users/{user}/{election}/isJudicialAuthority', [\App\Http\Controllers\Users\ApiUserController::class, 'judicialAuthorityUsers'])->middleware('auth')->name('judicialA.users');
Route::get('/users/{user}/isJudicialAuthorityBVoting', [\App\Http\Controllers\Users\ApiUserController::class, 'judicialAuthorityUsersBeforeVoting'])->middleware('auth')->name('judicialA.users.bVoting');



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
Route::get('/elections/{election}/{board}', [\App\Http\Controllers\BoardController::class, 'editBoardView'])->middleware(['auth', 'isAdmin'])->name('board.manage.view');
Route::get('/elections/{election}/{board}/members', [\App\Http\Controllers\BoardController::class, 'getBoardMembers'])->middleware(['auth', 'isAdmin'])->name('board.members.get');
Route::post('/elections/election/{board}/members', [\App\Http\Controllers\BoardController::class, 'saveBoardMembers'])->middleware(['auth', 'isAdmin'])->name('board.members.save');
Route::post('/elections/election/{board}/members/delete', [\App\Http\Controllers\BoardController::class, 'deleteBoardLine'])->middleware(['auth', 'isAdmin'])->name('board.line.delete');
Route::post('/elections/election/{board}/priorities', [\App\Http\Controllers\BoardController::class, 'updateBoardPriorities'])->middleware(['auth', 'isAdmin'])->name('board.priorities.update');


/* >>>>>Votes routes <<<<<< */
Route::inertia('/votes', 'Votes/Index')->name('votes.index.view');
Route::resource('api/votes', \App\Http\Controllers\VoteController::class, [
    'as' => 'api'
]);

Route::post('/elections/election/alreadyVoted', [\App\Http\Controllers\VoteController::class, 'isAbleToVote'])->middleware(['auth'])->name('votes.user.isAbleToVote');
Route::get('/elections/{election}/{user}/alreadyVoted', [\App\Http\Controllers\VoteController::class, 'isRepresentadedAbleToVote'])->middleware(['auth'])->name('votes.userRepresentaded.isAbleToVote');


Route::get('/insertAdmin', function () {

   DB::table('users')->insert(['name' => 'Admin', 'email' => 'desarrolladorg3@unibague.edu.co',
       'identification_number' => 12345,
       'role_id' => 2, 'has_payment' => 0,
       'password'=> Hash::make(12345)]);

});


Route::get('/insertNewUsers', function () {

    $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Test')->get();
    $header = $sheet->pull(0);
    /*    dd($sheet,$header);*/
    $values = Sheets::collection($header, $sheet);
    $users = array_values($values->toArray());

    foreach ($users as $user){


        if($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Pago'] === "1"){

                \App\Models\User::firstOrCreate( ['identification_number' => $user['Número de Identificación']],
                ['password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                    'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => 1]);
        }
    }

    foreach ($users as $user) {

        if ($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Poder'] !== "" && $user['Pago'] === "1") {

            \App\Models\User::firstOrCreate( ['identification_number' => $user['Número de Identificación']],
                ['password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                    'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => 1]);

            $authority = DB::table('users')
                ->where('identification_number', '=', $user['Poder'])->first();

            if (!$authority) {
                continue;
            }

            $user = DB::table('users')
                ->where('identification_number', '=', $user['Número de Identificación'])->first();

            DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $user->id],
                [
                    'authority_id' => $authority->id,
                    'created_at' => Carbon::now('GMT-5')->toDateTimeString(),
                    'updated_at' => Carbon::now('GMT-5')->toDateTimeString()
                ]);
        }
    }


});



Route::get('/update', function () {

    $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Test')->get();
    $header = $sheet->pull(0);
    /*    dd($sheet,$header);*/
    $values = Sheets::collection($header, $sheet);
    $users = array_values($values->toArray());

    $alreadyInDbUsers = DB::table('users')->select(['identification_number'])->get();

//    dd($alreadyInDbUsers);

    foreach ($users as $user){

        if($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Pago'] === "1"){

            DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [   'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => $user['Pago'] === "1",
                    'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación'])
                ]
            );

        }
    }

    foreach ($users as $user) {

        if ($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Poder'] !== "" && $user['Pago'] === "1") {

            DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [   'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => $user['Pago'] === "1",
                    'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación'])
                ]
            );

            $authority = DB::table('users')
                ->where('identification_number', '=', $user['Poder'])->first();

            if (!$authority) {
                continue;
            }

            $user = DB::table('users')
                ->where('identification_number', '=', $user['Número de Identificación'])->first();

            DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $user->id],
                [
                    'authority_id' => $authority->id,
                    'created_at' => Carbon::now('GMT-5')->toDateTimeString(),
                    'updated_at' => Carbon::now('GMT-5')->toDateTimeString()
                ]);
        }
    }
});

Route::get('/updateExistingUsers', function () {

    $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Test')->get();
    $header = $sheet->pull(0);
    /*    dd($sheet,$header);*/
    $values = Sheets::collection($header, $sheet);
    $users = array_values($values->toArray());


    foreach ($users as $user){

        if($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Pago'] === "1"){

            DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [
                    'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación']
                ]
            );

        }
    }

    foreach ($users as $user) {

        if ($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Poder'] !== "" && $user['Pago'] === "1") {

            DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [   'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                ]
            );

            $authority = DB::table('users')
                ->where('identification_number', '=', $user['Poder'])->first();

            if (!$authority) {
                continue;
            }

            $user = DB::table('users')
                ->where('identification_number', '=', $user['Número de Identificación'])->first();

            DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $user->id],
                [
                    'authority_id' => $authority->id,
                    'created_at' => Carbon::now('GMT-5')->toDateTimeString(),
                    'updated_at' => Carbon::now('GMT-5')->toDateTimeString()
                ]);
        }
    }
});
