<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('identification_number')->unique();
            $table->foreignId('role_id')->nullable()->default(1)->constrained()->nullOnDelete();
            $table->string('password');
            $table->boolean('has_payment');
            $table->timestamp('email_verified_at')->nullable();
            $table->foreignId('current_team_id')->nullable();
            $table->text('profile_photo_path')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('users')->insert(['name' => 'Admin G3', 'email' => 'desarrolladorg3@unibague.edu.co',
            'identification_number' => 12345,
            'role_id' => 2, 'has_payment' => 0,
            'password'=> \Illuminate\Support\Facades\Hash::make(12345)]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
