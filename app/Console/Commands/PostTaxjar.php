<?php

namespace App\Console\Commands;

use TaxJar\Client;
use TaxJar\Exception as TException;
use App\Models\SalesOrderRaw;
use Illuminate\Console\Command;


class PostTaxjar extends Command
{
    protected $signature = 'taxjar:post';

    protected $description = 'Post saved sales order to Taxjar.';

    public function handle()
    {
        $taxjar = Client::withApiKey(config('taxjar.api'));
       // $taxjar->setApiConfig('api_url', Client::DEFAULT_API_URL); //  DEFAULT_API_URL / SANDBOX_API_URL
        $taxjar->setApiConfig('api_url', 'https://api.taxjar.com/v2/'); //  DEFAULT_API_URL / SANDBOX_API_URL

        SalesOrderRaw::where('posted_to_taxjar', false)->chunk(10, function ($sales) use ($taxjar) {
            foreach($sales as $sale) {
                try {
                    $data = $sale['data'];

                    $items = array_map(function ($i) {
                        return [
                            'id' => $i['id'],
                            'quantity' => $i['qty'],
                            'product_identifier' => $i['code'],
                            'description' => $i['name'],
                            'unit_price' => $i['unitPrice'],
                            'discount' => $i['discount'],
                            // 'sales_tax' => null
                        ];
                    }, $data['lineItems']);

                    $result = $taxjar->createOrder([
                        'transaction_id' => $data['id'],
                        'transaction_date' => null,
                        'customer_id' => $data['memberId'],
                        'from_country' => null,
                        'from_zip' => null,
                        'from_state' => null,
                        'from_city' => null,
                        'from_street' => null,
                        'to_country' => $data['billingCountry'],
                        'to_zip' => $data['deliveryPostalCode'],
                        'to_state' => $data['deliveryState'],
                        'to_city' => $data['deliverCity'],
                        'to_street' => $data['deliverAddress1'],
                        'amount' => $data['total'],
                        'shipping' => $data['freightTotal'],
                        // 'sales_tax' => null,
                        'line_items' => $items
                    ]);

                    var_dump($result);
                } catch (TException $e) {
                    $this->error($e->getMessage());
                }
            }
        });
    }
}
