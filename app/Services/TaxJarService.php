<?php

namespace App\Services;

use TaxJar\Client;
use App\Models\SalesOrderRaw;
use App\Services\Cin7Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxJarService
{
    private $taxjarClient;

    public function __construct(){
        $this->taxjarClient = Client::withApiKey(config('taxjar.key'));
        $this->taxjarClient->setApiConfig('headers', [
            'x-api-version' => '2022-01-24'
        ]);
        $this->taxjarClient->setApiConfig('base_uri', "https://api.taxjar.com/v2/");
    }

    public function getCustomerIds(){
        return $this->taxjarClient->listCustomers();
    }

    public function getTransactionIds($from_date){
        return $this->taxjarClient->listOrders([
            'from_transaction_date' => $from_date,
            'to_transaction_date' => date("Y/m/d"),
            'provider'=> 'api',
        ]);
    }

    public function createTransaction($transactionObject){
        $transaction = $this->taxjarClient->createOrder($transactionObject);
        return $transaction;
    }

    public function deleteTransaction($transactionId){
        $this->taxjarClient->deleteOrder($transactionId);
    }

    public function createCustomer($customerObject){
        $customer = $this->taxjarClient->createCustomer($customerObject);
    }
    public function getRegisterUser()
    {
        return $this->taxjarClient->listCustomers();
    }
}
