<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TaxJar\Client;
use TaxJar\Exception as TException;

class TaxjarController extends Controller
{
    public $TAXJAR_API, $client;
    public function __construct()
    {
        $this->TAXJAR_API = env('TAXJAR_API_LIVE');
        $this->client = Client::withApiKey($this->TAXJAR_API);
        $this->client->setApiConfig('api_url', Client::DEFAULT_API_URL); //  DEFAULT_API_URL / SANDBOX_API_URL
    }


    public function createtaxjarorder (Request $request)
    {
        try {
            $order = $this->client->createOrder([
                'transaction_id' => '1234',
                'transaction_date' => '2015/05/14',
                'customer_id' => '1234',
                'from_country' => 'US',
                'from_zip' => '92093',
                'from_state' => 'CA',
                'from_city' => 'La Jolla',
                'from_street' => '9500 Gilman Drive',
                'to_country' => 'US',
                'to_zip' => '90002',
                'to_state' => 'CA',
                'to_city' => 'Los Angeles',
                'to_street' => '123 Palm Grove Ln',
                'amount' => 16.5,
                'shipping' => 1.5,
                'sales_tax' => 0.95,
                'line_items' => [
                    [
                        'id' => '1',
                        'quantity' => 1,
                        'product_identifier' => '12-34243-9',
                        'description' => 'Fuzzy Widget',
                        'unit_price' => 15.0,
                        'discount'=> 0,
                        'sales_tax' => 0.95
                    ]
                ]
            ]);

            return $order;

        } catch (TException $e) {


            return [
                "code" => $e->getStatusCode(),
                "message" => $e->getMessage()
            ];
          }
    }

    public function createtaxjarcustomer(Request $request)
    {
        try {
            $customer = $this->client->createCustomer([
                'customer_id' => '12345',
                'exemption_type' => 'wholesale',
                'name' => 'Dunder Mifflin Paper Company 2',
                'exempt_regions' => [
                    [
                    'country' => 'US',
                    'state' => 'FL'
                    ],
                    [
                    'country' => 'US',
                    'state' => 'PA'
                    ]
                ],
                'country' => 'US',
                'state' => 'PA',
                'zip' => '18504',
                'city' => 'Scranton',
                'street' => '1726 Slough Avenue'
            ]);
            return $customer;
        }  catch (TException $e) {


            return [
                "code" => $e->getStatusCode(),
                "message" => $e->getMessage()
            ];
          }
    }

    public function gettaxjarcustomer(Request $request)
    {
        try {
            $customer = $this->client->listCustomers();
            return $customer;
        }  catch (TException $e) {


            return [
                "code" => $e->getStatusCode(),
                "message" => $e->getMessage()
            ];
          }
    }
}
