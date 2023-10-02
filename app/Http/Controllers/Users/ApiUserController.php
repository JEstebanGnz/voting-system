<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetAllUsersRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Election;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $allUsers = DB::table('users as u')->where('has_payment', '=', 1)->orderBy('name', 'ASC')->get();
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

}
