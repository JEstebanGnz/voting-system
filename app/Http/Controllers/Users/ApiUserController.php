<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetAllUsersRequest;
use App\Http\Requests\UpdateUserRoleRequest;
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
}
