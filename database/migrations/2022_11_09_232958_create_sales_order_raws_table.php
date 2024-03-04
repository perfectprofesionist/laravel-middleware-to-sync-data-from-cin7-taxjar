<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_order_raws', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid');
            $table->json('data');
            $table->timestamp('modified_at');
            $table->boolean('posted_to_taxjar')->default(false);
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
        Schema::dropIfExists('sales_order_raws');
    }
};
