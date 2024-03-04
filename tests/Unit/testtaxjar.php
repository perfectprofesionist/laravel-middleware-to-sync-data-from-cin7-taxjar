<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;
use App\Services\Cin7Service;
use Illuminate\Support\Carbon;
use App\Services\TaxJarService;
use Illuminate\Support\Facades\Http;

class SyncService
{
    private $taxJarService, $cin7Service;

    public function __construct()
    {
        $this->taxJarService = new TaxJarService();
        $this->cin7Service = new Cin7Service();
    }

    public function syncSaleOrdersTransactions()
    {
        $this->deleteTransaction();
        $this->convertSaleOrderToTransaction();
        $this->syncCustomers();

    }

    public function convertSaleOrderToTransaction()
    {

        $saleOrders = $this->cin7Service->fetchSalesOrder();
        $todayDate = date('Y/m/d');
        $date = Carbon::now()->format('Y-m-d');
        try {
            $transactionIds = $this->taxJarService->getTransactionIds( $date );
        }catch(Exception $e)
        {
            dump($todayDate);
            dump($e);
            dd( $date);
        }
        $saleOrdersToAdd = [];
        foreach ($saleOrders as $saleOrder) {
            if (!in_array($saleOrder['id'], (array) $transactionIds)) {
                array_push($saleOrdersToAdd, $saleOrder);
            }
        }

        dump($saleOrdersToAdd );

        foreach ($saleOrdersToAdd as $saleOrder) {
            $line_items = [];
            foreach ($saleOrder['lineItems'] as $item) {
                array_push($line_items, [
                    'id' => $item['id'],
                    'quantity' => $item['qty'],
                    'product_identifier' => $item['productId'],
                    'unit_price' => $item['unitPrice'],
                    'sales_tax' =>  '0'
                ]);
            }
            if (Str::length($saleOrder['billingPostalCode']) != 5) {
                $zipcode =  '0' . $saleOrder['billingPostalCode'];
            } elseif (Str::length($saleOrder['billingPostalCode']) == 5) {
                $zipcode =  $saleOrder['billingPostalCode'];
            }

            $new_transaction = [
                'transaction_id' => '1445',
                'transaction_date' => $saleOrder['createdDate'],
                'to_country' =>  $saleOrder['billingCountry'],
                'to_zip' =>    $zipcode,
                'to_state' => $saleOrder['billingState'],
                'to_city' =>   $saleOrder['billingCity'],
                'to_street' =>  $saleOrder['billingAddress1'],
                'amount' => $saleOrder['total'],
                'shipping' => $saleOrder['freightTotal'],
                'sales_tax' => $saleOrder['taxRate'],
                'line_items' => $line_items,
                'provider'=> 'api',
            ];
            try {
               $error = $this->taxJarService->createTransaction($new_transaction);
               dump( $error);
            } catch (Exception $e) {
                dump('new transaction');
                dump($new_transaction);
                dd($e);
            }
        }
    }

    public function syncCustomers()
    {

        $cin7users = $this->cin7Service->getUser();
        $registerusers = $this->taxJarService->getRegisterUser();
        $usersToAdd = [];
        foreach ($cin7users as $cin7user) {
            if (!in_array($cin7user['id'], (array) $registerusers)) {
                array_push($usersToAdd, $cin7user);
            }
        }

        foreach ($usersToAdd as $adduser) {
            $this->taxJarService->createCustomer([
                'customer_id' => $adduser['id'],
                'exemption_type' =>  'other',
                'name' => $adduser['firstName'],
            ]);
        }
    }

    public function deleteTransaction()
    {
        $saleOrders = $this->cin7Service->fetchSalesOrder();
        $todayDate = date("Y/m/d");
        $transactionIds = $this->taxJarService->getTransactionIds($todayDate);
        $saleOrderIds = array_map(function ($value) {
            return $value['id'];
        }, $saleOrders);
        foreach ($transactionIds as $transactionId) {
            if (!in_array($transactionId, $saleOrderIds)) {
                $this->taxJarService->deleteTransaction($transactionId);
            }
        }
    }
}
//===========================
<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class Cin7Service
{
    private $cin7Client;

    public function __construct()
    {
        $this->cin7Client = Http::withBasicAuth(config('cin7.username'), config('cin7.password'));
    }

    public function fetchSalesOrder()
    {


        $record = null;

        // $data = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders?rows=250&order=id")->json();
        // $from =  array_values($data)[0];
        // $to = end($data);
        // dump($from['id']); //73
        // dump($to['id']); //494
        // $a = $to['id']; //494
        $data = [];
        // $data_4 = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders?rows=250&fields=id&order=id&where=id>$a")->json();
        // dump($data_4);
        $page = 1;
        while (true) {
            dump("loopcounter: $page");
            try{
                $data_4 = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders?rows=250&fields=id&order=id&page=$page")->json();
            }
            catch(Exception $e){
                break;
            }
            if ($data_4 === []) {
                break;
            }
            $page =  $page + 1;
            dump($data_4);
            $data = array_merge($data, (array) $data_4);
        }
        $last= $data[count($data) - 1]['id'];
        dump($last);
        Storage::disk('local')->put('example.txt', $last);
        dd(count($data));

    }

    public function getUser()
    {
        $data = $this->cin7Client->get("https://api.cin7.com/api/v1/Contacts")->json();
        $page = 1;
        while (true) {
            dump("loopcounter: $page");
            try{
                $data_4 = $this->cin7Client->get("https://api.cin7.com/api/v1/Contacts?rows=250&page=$page")->json();
            }
            catch(Exception $e){
                break;
            }
            if ($data_4 === []) {
                break;
            }
            $page =  $page + 1;
            dump($data_4);
            $data = array_merge($data, (array) $data_4);
        }
        return $data;
    }
};
