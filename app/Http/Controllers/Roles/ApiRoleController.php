<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\Request;

class ApiRoleController extends Controller
{
    public function index()
    {
        $roles = Role::all(['id', 'name', 'created_at', 'customId']);
        return response($roles);
    }

    public function store(UpdateRoleRequest $request)
    {
        $role = Role::create($request->all());
        return response()->json(['message' => 'Rol creado exitosamente']);
    }

    public function destroy(DeleteRoleRequest $request, Role $role)
    {
        $role->delete();
        return response()->json(['message' => 'Rol eliminado exitosamente']);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        if ($role === null) {
            return response()->json(['error' => 'El rol seleccionado no existe', 404]);
        }
        $role->customId = $request->input('customId');
        $role->name = $request->input('name');
        $role->save();
        return response()->json(['message' => 'Rol actualizado exitosamente', 200]);

    }


}
