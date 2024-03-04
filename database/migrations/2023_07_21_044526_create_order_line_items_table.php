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
        Schema::create('order_line_items', function (Blueprint $table) {
            $table->id();
            $table->string('lineItemId');
            $table->string('qty')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('sort')->nullable();
            $table->string('barcode')->nullable();
            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->string('discount')->nullable();
            $table->string('parentId')->nullable();
            $table->float('unitCost',10,2)->default('0.00');
            $table->float('unitPrice',10,2)->default('0.00');
            $table->string('productId')->nullable();
            $table->string('styleCode')->nullable();
            $table->string('qtyShipped')->nullable();
            $table->string('transactionId')->nullable();
            $table->string('productOptionId')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_line_items');
    }
};
