<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;

use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index');
    }
}
