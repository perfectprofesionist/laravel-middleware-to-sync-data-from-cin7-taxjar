<?php

namespace App\Console\Commands;

use App\Models\SalesOrderRaw;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchCin7 extends Command
{
    protected $signature = 'cin7:fetch';

    protected $description = 'Fetch sales order from Cin7 and save it to database.';

    public function handle()
    {
        $data = Http::withBasicAuth(config('cin7.username'), config('cin7.password'))
            ->get("https://api.cin7.com/api/v1/SalesOrders?page=1&rows=1")
            ->json();
        foreach($data as $sale) {
            $x = SalesOrderRaw::firstOrCreate(
                ['uid' => $sale['id']],
                [
                    'uid' => $sale['id'],
                    'data' => $sale,
                    'modified_at' => Carbon::create($sale['modifiedDate'])
                ]
            );
        }
    }
}
