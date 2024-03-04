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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('orderId');
            $table->string('fax')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('stage')->nullable();
            $table->float('total',10,2)->default('0.00');
            $table->string('isVoid')->nullable();
            $table->string('mobile')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->nullable();
            $table->string('company')->nullable();
            $table->string('taxRate')->nullable();
            $table->string('branchId')->nullable();
            $table->string('lastName')->nullable();
            $table->string('memberId')->nullable();
            $table->string('createdBy')->nullable();
            $table->string('editStatus')->nullable();
            $table->string('firstName')->nullable();
            $table->string('reference')->nullable();
            $table->string('surcharge')->nullable();
            $table->string('taxStatus')->nullable();
            $table->string('costCenter')->nullable();
            $table->string('isApproved')->nullable();
            $table->string('billingCity')->nullable();
            $table->string('branchEmail')->nullable();
            $table->string('createdDate')->nullable();
            $table->string('invoiceDate')->nullable();
            $table->string('memberEmail')->nullable();
            $table->string('processedBy')->nullable();
            $table->string('projectName')->nullable();
            $table->string('voucherCode')->nullable();
            $table->string('billingState')->nullable();
            $table->string('currencyCode')->nullable();
            $table->string('currencyRate')->nullable();
            $table->string('deliveryCity')->nullable();
            $table->string('deliveryState')->nullable();
            $table->float('discountTotal')->default('0.00');
            $table->string('invoiceNumber')->nullable();
            $table->string('salesPersonId')->nullable();
            $table->string('billingCompany')->nullable();
            $table->string('billingCountry')->nullable();
            $table->string('dispatchedDate')->nullable();
            $table->string('billingAddress1')->nullable();
            $table->string('billingAddress2')->nullable();
            $table->string('deliveryCountry')->nullable();
            $table->string('billingFirstName')->nullable();
            $table->string('billingLastName')->nullable();
            $table->string('deliveryLastName')->nullable();
            $table->string('billingPostalCode')->nullable();
            $table->string('deliveryFirstName')->nullable();
            $table->string('alternativeTaxRate')->nullable();
            $table->string('deliveryPostalCode')->nullable();
            $table->string('modifiedDate')->nullable();
            $table->string('freightDescription')->nullable();
            $table->string('deliveryAddress1')->nullable();
            $table->string('freightTotal',10,2)->default('0.00');
            $table->float('productTotal',10,2)->default('0.00');
            $table->tinyInteger('is_processed')->default('0');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
};
