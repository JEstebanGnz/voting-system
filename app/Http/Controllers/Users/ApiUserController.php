<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetAllUsersRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Election;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApiUserController extends Controller
{
    public function index(GetAllUsersRequest $request)
    {
        return User::with('role')->orderBy('name')->get();
    }

    public function getUserRole()
    {
        return auth()->user()->role->customId;
    }

    public function updateUserRole(User $user, UpdateUserRoleRequest $request)
    {
        $user->role_id = $request->roleId;
        $user->save();
        return response()->json(['message' => 'El rol del usuario ha sido actualizado exitosamente']);
    }

    public function getSuitableUsersToAdd()
    {

        $allUsers = DB::table('users as u')->where('has_payment', '=', 1)
            ->where('external_user', '=', false)->orderBy('name', 'ASC')->get();
        $users = [];
        foreach($allUsers as $user){

            $userIsAlreadyOnElection = DB::table('board_members as bm')->where('head_id', '=', $user->id)
                ->orWhere('substitute_id', '=', $user->id)->first();
            if(!$userIsAlreadyOnElection){
                $users [] = $user;
            }
        }
        return response()->json($users);
    }

    public function judicialAuthorityUsersBeforeVoting(User $user)
    {

        $delegatedUsers = DB::table('user_judicial_authority as uja')->where('uja.authority_id', '=', $user->id)
            ->join('users as u', 'u.id','=','uja.user_id')
            ->where('u.has_payment', '=', 1)->select(['u.id', 'u.name'])
            ->get();

        return response()->json($delegatedUsers);
    }


    public function judicialAuthorityUsers(User $user, Election $election)
    {

        $delegatedUsers = DB::table('user_judicial_authority as uja')->where('uja.authority_id', '=', $user->id)
            ->join('users as u', 'u.id','=','uja.user_id')
            ->where('u.has_payment', '=', 1)->select(['u.id', 'u.name'])
            ->get();

        $finalUsers = [];

        foreach ($delegatedUsers as $delegatedUser) {
            $delegatedAlreadyVoted = DB::table('votes as v')->where('v.user_id', '=', $delegatedUser->id)
            ->where('v.election_id', '=', $election->id)->first();
            if(!$delegatedAlreadyVoted){
                $finalUsers [] = $delegatedUser;
            }
        }

        return response()->json($finalUsers);
    }


/*
        //Parse the name and email
        foreach ($rows as $key => $row) {
            try {

                [$registro, $identificacion, $nombre, $email, $direccion,
                    $telefono, $catAsociado, $aporte, $medioPago, $tipoIdentificacion, $name, $asistio, $apoderadoExterno, $poder,
                    $pago, $monto, $control,$rubbish, $lorepresenta, $cedula] = explode(",", $row);

            } catch (\Exception $e) {
                $message = 'Has ingresado los datos de manera incorrecta, lo que produjo el siguiente error en el servidor: ' . $e->getMessage();
                return response()->json(['message' => $message], 400);
            }
            $rows[$key] = [
                'Nombre para votación' => $name,
                'Correo Electrónico' => $email,
                'Número de Identificación' => $identificacion,
                'Asistió' => $asistio,
                'Apoderado externo' => $apoderadoExterno,
                'Poder' => $poder,
                'Pago' => $pago
            ];
        }

        dd($rows);

        //Now, lets filter a detect if there are errors

        foreach ($rows as $row) {
            if ($row['name'] === '') {
                $message = 'La siguiente entrada no cumple con el formato dispuesto (falta nombre)' .
                    $row['name'] . ' ' . $row['email'];
                return response()->json(['message' => $message], 400);
            }

            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $message = 'La siguiente entrada no cumple con el formato dispuesto (email invalido)' .
                    $row['name'] . ' ' . $row['email'];
                return response()->json(['message' => $message], 400);
            }


            if (!str_contains($row['monitoringType'], 'académica') && !str_contains($row['monitoringType'], 'administrativa')) {
                $message = 'Solo se permite monitorías académicas o administrativas';
                return response()->json(['message' => $message], 400);
            }

        }

        //The verification process has ended successfully

        $counter = 0;
        foreach ($rows as $row) {
            //Check if user exist and create it if dont
            $user = User::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('automatic_generate_password'),
                    'role_id' => 3
                ]
            );

            $counter++;
        }*/

//        return response()->json(['message' => 'Se han importado exitosamente ' . $counter . ' usuarios']);


}
