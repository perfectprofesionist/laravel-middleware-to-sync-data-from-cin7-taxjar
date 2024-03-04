<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class Cin7Service
{
    private $cin7Client;

    public function __construct()
    {
        $this->cin7Client = Http::withBasicAuth(config('cin7.username'), config('cin7.password'));
    }

    public function fetchSalesOrder($page)
    {

        //$data = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders/84920")->json();
        $data = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders?row=250&page=".$page."&order=DispatchedDate desc")->json();
       // $data = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders?row=250&order=id desc")->json();
        // $data = $this->cin7Client->get("https://api.cin7.com/api/v1/SalesOrders?where=billingCountry>=' '&where=billingPostalCode>=' '&where=billingState>=' '&order=CreatedDate desc&rows=5&page=15")->json();

        return $data;
    }

    public function getUser()
    {
        $data = $this->cin7Client->get("https://api.cin7.com/api/v1/Contacts")->json();
        $page = 1;
        while (true) {
            dump("loopcounter: $page");
            try{
                $data_4 = $this->cin7Client->get("https://api.cin7.com/api/v1/Contacts?rows=250&order=id desc")->json();
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
