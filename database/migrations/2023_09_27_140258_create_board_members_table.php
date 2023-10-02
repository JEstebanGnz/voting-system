<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBoardMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('board_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained();
            $table->foreignId('head_id');
            $table->foreign('head_id')->references('id')->on('users');
            $table->foreignId('substitute_id')->nullable();
            $table->foreign('substitute_id')->references('id')->on('users');
            $table->smallInteger('priority');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('board_members');
    }
}
