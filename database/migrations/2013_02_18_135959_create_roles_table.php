<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->smallInteger('customId');
            $table->timestamps();
        });

        //Create the first two roles
        \Illuminate\Support\Facades\DB::table('roles')->insert(
            [
               ['name' => 'voter', 'customId' => 3],
               ['name' => 'admin', 'customId' => 10]
            ]
        );

    }




    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
}
