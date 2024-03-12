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
/* >>>>> Authentication routes <<<<<< */
Route::inertia('/login', 'Custom/CustomLogin')->name('login');
Route::post('/loginValidation', [\App\Http\Controllers\AuthController::class, 'loginValidation'])->name('login.validation');
Route::get('/', [\App\Http\Controllers\AuthController::class, 'handleRoleRedirect'])->middleware(['auth'])->name('role.redirect');


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

/* >>>>>Elections routes <<<<<< */
Route::inertia('/elections', 'Elections/Index')->middleware(['auth', 'isAdmin'])->name('elections.index.view');
Route::resource('api/elections', \App\Http\Controllers\ElectionController::class, [
    'as' => 'api'
]);
Route::post('/api/elections/{election}/setActive', [\App\Http\Controllers\ElectionController::class, 'setActive'])->middleware(['auth', 'isAdmin'])->name('api.elections.setActive');
Route::post('/api/elections/{election}/deactivate', [\App\Http\Controllers\ElectionController::class, 'deactivate'])->middleware(['auth', 'isAdmin'])->name('api.elections.deactivate');
Route::get('/api/elections/{electionId}/boards', [\App\Http\Controllers\ElectionController::class, 'getBoards'])->middleware(['auth'])->name('api.elections.boards');
Route::get('/elections/active', [\App\Http\Controllers\ElectionController::class, 'getActive'])->middleware(['auth'])->name('elections.active');
Route::get('/election/report/{election}', [\App\Http\Controllers\ElectionController::class, 'generateReport'])->middleware(['auth', 'isAdmin'])->name('elections.report');

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
Route::post('/users/manualUpdate', [\App\Http\Controllers\Users\ApiUserController::class, 'manualUpdate'])->middleware('auth')->name('users.manualUpdate');

/* >>>>>Votes routes <<<<<< */
Route::inertia('/votes', 'Votes/Index')->name('votes.index.view');
Route::resource('api/votes', \App\Http\Controllers\VoteController::class, [
    'as' => 'api'
]);
Route::post('/elections/election/alreadyVoted', [\App\Http\Controllers\VoteController::class, 'isAbleToVote'])->middleware(['auth'])->name('votes.user.isAbleToVote');
Route::get('/elections/{election}/{user}/alreadyVoted', [\App\Http\Controllers\VoteController::class, 'isRepresentadedAbleToVote'])->middleware(['auth'])->name('votes.userRepresentaded.isAbleToVote');




/* >>>>>Test routes <<<<<< */

Route::get('/insertAdmin', function () {

   DB::table('users')->insert(['name' => 'Admin', 'email' => 'desarrolladorg3@unibague.edu.co',
       'identification_number' => 12345,
       'role_id' => 2, 'can_vote' => 0,
       'password'=> Hash::make(12345)]);

});



Route::get('/createOrUpdateUsers', function () {

    $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Asistencia')->get();
    $header = $sheet->pull(0);
    $values = Sheets::collection($header, $sheet);
    $users = array_values($values->toArray());

    foreach ($users as $user) {

        try{
            //Si es un afiliado que se encuentra al día
            if($user['Voto'] === "1" && $user['Apoderado externo'] === ""){

                $voter = \App\Models\User::firstOrCreate
                (
                    ['identification_number' => $user['Número de Identificación']],
                    [
                        'name' => $user['Nombre para votación'],
                        'email' => $user['Correo electrónico'],
                        /*'role_id' => 1,*/
                        'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                        'can_vote' => 1,
                        'external_user' => 0,
                    ]
                );

                //Si es un afiliado que votará él mismo y en persona, entonces lo agregamos y ya.
                if($user['Poder'] === ""){
                    continue;
                }

                //Si es un afiliado el cual ejercerá su voto mediante poder, entonces debemos primero buscar esa cédula del que votará por él
                elseif ($user['Poder'] !== ""){

                    //El que votará por él, ya está creado en la BD?
                    $judicialAuthority = DB::table('users')->where('identification_number','=', $user['Poder'])->first();

                    //Si ya está en la BD, entonces simplemente hacemos la asignación
                    if($judicialAuthority){
                        DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $voter->id],
                            [
                                'authority_id' => $judicialAuthority->id,
                                'created_at' => Carbon::now()->toDateTimeString(),
                                'updated_at' => Carbon::now()->toDateTimeString()
                            ]);
                    }

                    //Si el que votará por él no está en la BD, debemos crearlo a él y ahí sí hacer la asignación
                    else {

                        //filtramos el array de $users y obtenemos al usuario que será el que votará por el afiliado activo

                        $judicialAuthority = null;

                        foreach ($users as $item) {
                            // Check if the current element satisfies the condition
                            if ($item['Número de Identificación'] === $user['Poder']) {
                                // Return the first element that satisfies the condition and stop iterating
                                $judicialAuthority = $item;
                            }
                        }

                        //Si el que votará por él, es otro afiliado que se encuentra al día, lo creamos también y luego hacemos la asignación
                        if ($judicialAuthority['Voto'] === "1" && $judicialAuthority['Apoderado externo'] === "") {
                            $judicialAuthorityCreated = \App\Models\User::firstOrCreate
                            (
                                ['identification_number' => $judicialAuthority['Número de Identificación']],
                                [
                                    'name' => $judicialAuthority['Nombre para votación'],
                                    'email' => $judicialAuthority['Correo electrónico'],
                                    /*'role_id' => 1,*/
                                    'password' => \Illuminate\Support\Facades\Hash::make($judicialAuthority['Número de Identificación']),
                                    'can_vote' => 1,
                                    'external_user' => 0,
                                ]
                            );

                            DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $voter->id],
                                [
                                    'authority_id' => $judicialAuthorityCreated->id,
                                    'created_at' => Carbon::now()->toDateTimeString(),
                                    'updated_at' => Carbon::now()->toDateTimeString()
                                ]);

                        }

                        //Si el que votará por él, es un apoderado EXTERNO, lo creamos también y luego hacemos la asignación
                        elseif ($judicialAuthority['Apoderado externo'] === "1" && $judicialAuthority['Voto'] === "1"){
                            $judicialAuthorityCreated = \App\Models\User::firstOrCreate
                            (
                                ['identification_number' => $judicialAuthority['Número de Identificación']],
                                [
                                    'name' => $judicialAuthority['Nombre para votación'],
                                    'email' => $judicialAuthority['Correo electrónico'],
                                    /*'role_id' => 1,*/
                                    'password' => \Illuminate\Support\Facades\Hash::make($judicialAuthority['Número de Identificación']),
                                    'can_vote' => 0,
                                    'external_user' => 1,
                                ]
                            );

                            DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $voter->id],
                                [
                                    'authority_id' => $judicialAuthorityCreated->id,
                                    'created_at' => Carbon::now()->toDateTimeString(),
                                    'updated_at' => Carbon::now()->toDateTimeString()
                                ]);
                        }

                        if($judicialAuthority['Voto'] === ""){
                            continue;
                        }
                    }
                }
            }

            elseif ($user['Apoderado externo'] === "1" && $user['Voto'] === "1"){

                \App\Models\User::firstOrCreate
                (
                    ['identification_number' => $user['Número de Identificación']],
                    [
                        'name' => $user['Nombre para votación'],
                        'email' => $user['Correo electrónico'],
                        /*'role_id' => 1,*/
                        'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                        'can_vote' => 0,
                        'external_user' => 1,
                    ]
                );
            }
        } catch (\Exception $exception){
            continue;
        }
    }

});




Route::get('/insertNewUsers', function () {

    $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Asistencia')->get();
    $header = $sheet->pull(0);
    $values = Sheets::collection($header, $sheet);
    $users = array_values($values->toArray());

    dd($users);



   //Este primer foreach es para insertar únicamente a usuarios, no se tiene en cuenta los poderes
    foreach ($users as $user){

        //Si es una persona que se convertirá en asociado
        if($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" &&
            $user['Asistió'] === "1" && $user['Pago'] === "1" && $user['Apoderado externo'] === "" ){

                \App\Models\User::firstOrCreate( ['identification_number' => $user['Número de Identificación']],
                ['password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                    'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => 1]);
        }

        //Si es una persona que va únicamente en nombre de alguien a votar por esa persona, PERO NO SERÁ AFILIADO
        if($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" && $user['Apoderado externo'] === "1"){

            \App\Models\User::firstOrCreate( ['identification_number' => $user['Número de Identificación']],
                ['password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                    'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => 1,
                    'external_user' => 1]);
        }

    }

    //Este foreach ya es para realizar las correspondientes asignaciones de poderes
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
                    'name' => $user['Nombre para votación'],
                    'external_user' => 0,
                ]
            );
        }

        if($user['Correo electrónico'] !== "" && $user['Número de Identificación'] !== "" && $user['Apoderado externo'] === "1"){

            DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [
                    'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'external_user' => 1,
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

