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
        $sheet = Sheets::spreadsheet(env('POST_SPREADSHEET_ID'))->sheet('Respuestas')->get();
        $header = $sheet->pull(0);
        $values = Sheets::collection($header, $sheet);
        $users = array_values($values->toArray());
//    dd($users);

        foreach ($users as $user){

            \Illuminate\Support\Facades\DB::table('users')->updateOrInsert
            (
                ['email' => $user['Correo electrónico']],
                [   'identification_number' => $user['Número de Identificación'],
                    'name' => $user['Nombre'],
                    'role_id' => 1,
                    'has_payment' => $user['Pago'] === 'Sí' ,
                    'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación'])
                ]
            );
        }

        return 0;
    }
}
