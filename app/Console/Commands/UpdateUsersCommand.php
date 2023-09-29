<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Revolution\Google\Sheets\Facades\Sheets;

class UpdateUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update';

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
        /*$sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Respuestas')->get();
        $header = $sheet->pull(0);
        $values = Sheets::collection($header, $sheet);
        $users = array_values($values->toArray());
//    dd($users);

        foreach ($users as $user){

            \Illuminate\Support\Facades\DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [   'email' => $user['Correo electrónico'],
                    'name' => $user['Nombre para votación'],
                    'role_id' => 1,
                    'has_payment' => $user['Pago'] === 1 ,
                    'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación'])
                ]
            );
        }*/

        $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Test')->get();
        $header = $sheet->pull(0);
        $values = Sheets::collection($header, $sheet);
        $users = array_values($values->toArray());

        foreach ($users as $user){

            if(($user['Asistió'] === "1" && $user['Pago'] === "1" && $user['Monto'] !== "")
                || ($user['Poder'] !== "" && $user['Pago'] === "1" && $user['Monto'] !== "")){

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

                if ($user['Poder'] !== "" && $user['Pago'] === "1" && $user['Monto'] !== ""){

                    $authority = DB::table('users')
                        ->where('identification_number', '=', $user['Poder'])->first();

                    if(!$authority){
                        continue;
                    }

                    $user = DB::table('users')
                        ->where('identification_number', '=', $user['Número de Identificación'])->first();

                    DB::table('user_judicial_authority')->updateOrInsert(['authority_id' => $authority->id],
                        [
                            'user_id' => $user->id,
                            'created_at' => Carbon::now('GMT-5')->toDateTimeString(),
                            'updated_at' => Carbon::now('GMT-5')->toDateTimeString()
                        ]);
                }
            }
        }
        return 0;
    }
}
