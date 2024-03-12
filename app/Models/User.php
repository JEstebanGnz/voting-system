<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @mixin EloquentBuilder
 * @mixin QueryBuilder
 */

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'identification_number',
        'password',
        'role_id' => 1,
        'can_vote',
        'external_user'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];


    public function createOrUpdateUser($user, $usersArray)
    {
        if($user['Voto'] === 1 && $user['Apoderado externo'] === ""){

            $voter = DB::table('users')->updateOrInsert
            (
                ['identification_number' => $user['Número de Identificación']],
                [
                    'name' => $user['Nombre para votación'],
                    'email' => $user['Correo electrónico'],
                    'role_id' => 1,
                    'password' => \Illuminate\Support\Facades\Hash::make($user['Número de Identificación']),
                    'can_vote' => 1,
                    'external_user' => 0,
                ]
            );
            //Si es un afiliado que votará él mismo y en persona, entonces lo agregamos y ya.


            //Si es un afiliado el cual ejercerá su voto mediante poder, entonces debemos primero buscar esa cédula del que votará por él
            if ($user['Poder'] !== ""){

                self::createOrUpdateUserWithJudicialAuthority($user['Poder'], $voter, $usersArray);

            }
        }
    }


    public function createOrUpdateUserWithJudicialAuthority($judicialAuthorityIdentifier, $voterCreated, $usersArray)
    {

        //El que votará por él, ya está creado en la BD?
        $judicialAuthority = DB::table('users')->where('identification_number','=', $judicialAuthorityIdentifier)->first();

        //Si ya está en la BD, entonces simplemente hacemos la asignación
        if($judicialAuthority){
            DB::table('user_judicial_authority')->updateOrInsert(['user_id' => $voterCreated->id],
                [
                    'authority_id' => $judicialAuthority->id,
                    'created_at' => Carbon::now('GMT-5')->toDateTimeString(),
                    'updated_at' => Carbon::now('GMT-5')->toDateTimeString()
                ]);
        }

        //Si el que votará por él no está en la BD, debemos crearlo a él y ahí sí hacer la asignación
        else{
            //filtramos el array de $users y obtenemos al usuario que será el que votará por el user

            $judicialAuthority = array_filter($usersArray, function ($item) use($judicialAuthorityIdentifier){
                return $item['Número de Identificación'] === $judicialAuthorityIdentifier;
            });


        }

    }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        try {
            $roleNumber = Role::getRoleNumber($roleName);
        } catch (\RuntimeException $e) {
            return false;
        }
        return $this->role->customId >= $roleNumber;
    }
}
