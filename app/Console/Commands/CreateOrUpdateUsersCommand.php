<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpseclib3\Crypt\Hash;
use Revolution\Google\Sheets\Facades\Sheets;

class CreateOrUpdateUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createOrUpdate:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Asistencia')->get();
        $header = $sheet->pull(0);
        $values = Sheets::collection($header, $sheet);
        $users = array_values($values->toArray());

        foreach ($users as $user) {
            try{
                if($user['Número de Identificación'] !== "" && $user['Correo electrónico'] !== ""){
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
            }
            } catch (\Exception $exception){
                continue;
            }
        }
        Log::info('Users updated/created correctly');
        return 0;
    }
}
