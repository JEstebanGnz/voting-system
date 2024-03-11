<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    //

    public function redirectGoogleLogin()
    {
        return Socialite::driver('google')->redirect();
    }



    public function loginValidation(Request $request)
    {
        //Error messages
        $messages = [
            "email.required" => "El correo es obligatorio",
            "email.email" => "Formato de correo incorrecto",
            "email.exists" => "El correo ingresado no se encuentra en la base de datos",
            "password.required" => "Se requiere la contraseña para ingresar",
        ];

        // validate the form data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ], $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        } else {
            // attempt to log
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password ])) {
                // if successful -> redirect to roleRedirect
                return redirect()->intended(route('role.redirect'));
            }

            // if unsuccessful -> redirect back
            return redirect()->back()->withInput($request->only('email'))->withErrors([
                'approve' => 'Contraseña incorrecta, recuerda que es tu número de identificación',
            ]);
        }
    }


    public function handleRoleRedirect()
    {
        $user = auth()->user();
        if ($user->role_id === 1) {
            return Inertia::render('Votes/Index');
        }
        return Inertia::render('Elections/Index');
    }


    public function testLoginValidation(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('tests.index.view');
        }
        return back()->withErrors([
            'email' => 'Correo o contraseñas incorrectas, inténtelo nuevamente.',
        ]);
    }

}
