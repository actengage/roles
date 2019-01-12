<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::create('roles', function($table)
		{
			$table->increments('id');
			$table->integer('parent_id')->unsigned()->nullable();
			$table->foreign('parent_id')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
			$table->string('slug');
			$table->string('name');
			$table->text('description');
			$table->integer('order')->default(0);
			$table->timestamps();
		});

        Schema::create('roleables', function($table) {
			$table->increments('id');
            $table->integer('role_id')->unsigned();
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
            $table->morphs('roleable');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roleable');
		Schema::dropIfExists('roles');
    }
}
