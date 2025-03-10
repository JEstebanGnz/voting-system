<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Revolution\Google\Sheets\Facades\Sheets;

class InsertNewUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:insert';

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
        $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Respuestas')->get();
        $header = $sheet->pull(0);
        /*    dd($sheet,$header);*/
        $values = Sheets::collection($header, $sheet);
        $users = array_values($values->toArray());


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

        return 0;
    }
}
