<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->integer('merchandise_id')->unsigned();
			$table->double("quantity")->default('1');
			$table->timestamps();
			
			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('merchandise_id')->references('id')->on('merchandise');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('carts', function(Blueprint $table){
            $table->dropForeign('cart_user_id_foreign');
			$table->dropForeign('cart_merchandise_id_foreign');
        });
		
        Schema::dropIfExists('carts');
    }
}
