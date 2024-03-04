<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Cin7Service;
use App\Services\TaxJarService;
use App\Services\SyncService;
use DB;
use Mail;
use Illuminate\Support\Facades\Storage;
use File;
use Response;
class TestController extends Controller
{
    private $taxJarService, $cin7Service;

    public function __construct()
    {
        $this->taxJarService = new TaxJarService();
        $this->cin7Service = new Cin7Service();
    }


   public function formatedDate($date){
        $date=date_create($date);
        date_modify($date,"-1 days");
        return date_format($date,"Y-m-d");
    }

    public function createCSV(){
        $date = date("Y-m-d");
        $date = $this->formatedDate($date);
        $checkStatus = DB::table("error_logs_mail")->where(["sent_date"=>$date])->first();
        if(empty($checkStatus)){
            $logs = DB::table('error_logs')->where(["postedDate"=>$date])->get();
            $fileName = "error-log-".$date.".csv";
            $headers = array(
                'Content-Type' => 'text/csv'
            );
            if (!File::exists(public_path()."/error-logs")) {
                File::makeDirectory(public_path() . "/error-logs");
            }
            $filename =  public_path("error-logs/".$fileName);
            $handle = fopen($filename, 'w');
            fputcsv($handle, [
                "Order Id",
                "Error",
                "Date",
            ]);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->orderid,
                    $log->error,
                    $date,
                ]);

            }
            fclose($handle);
            DB::table("error_logs_mail")->insert(["sent_date"=>$date,"is_processed"=>0]);
            return Response::download($filename, "download.csv", $headers);
        }
    }

    public function sendErrorLogs(){
        $date = date("Y-m-d");
        $date = $this->formatedDate($date);
        $checkStatus = DB::table("error_logs_mail")->where(["sent_date"=>$date,"is_processed"=>"0"])->first();
        if(!empty($checkStatus)){
            Mail::send('emails.test', ['date'=>$date], function ($m) use ($date) {
                $m->from('noreply@noma.dev.hpprojects.net', 'Human Pixel');
                $pathToFile = public_path("error-logs/error-log-".$date.".csv");
                $m->attach($pathToFile);
                //$m->to("gunjit@team.humanpixel.com.au", "Gunjit Singh")->subject('Your Reminder!');
                $m->to("ritesh@yopmail.com", "Gunjit Singh")->subject('Error logs file for '.date('d M Y',strtotime($date)));
            });
            DB::table("error_logs_mail")->where(["sent_date"=>$date])->update(["is_processed"=>1]);
        }
    }

    public function test()
    {

        $saleOrdersToAdd = [];
        $saleOrders = DB::table("sales_orders")->where("orderId","69838")->where("invoiceDate","!=",NULL)->where("dispatchedDate","!=",NULL)->orderBy('orderId','desc')->limit(50)->get();
        //Log::info("I am here2",$saleOrders);
        foreach ($saleOrders as $row) {
            $lineItemsArr = [];
            $lineItems = DB::table("order_line_items")->where("transactionId",$row->orderId)->get();
            if(!empty($lineItems)){
                foreach($lineItems as $rows){
                    $lineItemsArr[] = [
                    "lineItemId"=> $rows->id,
                    "qty"=> $rows->qty,
                    "code"=> $rows->code,
                    "name"=> $rows->name,
                    "sort"=> $rows->sort,
                    "barcode"=> $rows->barcode,
                    "option1"=> $rows->option1,
                    "option2"=> $rows->option2,
                    "option3"=> $rows->option3,
                    "discount"=> $rows->discount,
                    "parentId"=> $rows->parentId,
                    "unitPrice"=> $rows->unitPrice,
                    "unitCost"=> $rows->unitCost,
                    "productId"=> $rows->productId,
                    "styleCode"=> $rows->styleCode,
                    "qtyShipped"=> $rows->qtyShipped,
                    "transactionId"=> $rows->transactionId,
                    "productOptionId"=> $rows->productOptionId,
                    ];

                }
                
            }

            $saleOrdersToAdd[] = [
                "deliveryCountry"=> $row->deliveryCountry,
                "orderId"=> $row->orderId,
                "billingCountry"=> $row->billingCountry,
                "discountTotal"=> $row->discountTotal,
                "billingPostalCode"=> $row->billingPostalCode,
                "deliveryPostalCode"=> $row->deliveryPostalCode,
                "deliveryState"=> $row->deliveryState,
                "billingState"=> $row->billingState,
                "freightTotal"=> $row->freightTotal,
                "taxRate"=> $row->taxRate,
                "taxStatus"=> $row->taxStatus,
                "productTotal"=> $row->productTotal,
                "taxRate"=> $row->taxRate,
                "total"=> $row->total,
                "deliveryAddress1"=> $row->deliveryAddress1,
                "billingCity"=> $row->billingCity,
                "billingAddress1"=> $row->billingAddress1,
                "deliveryCity"=> $row->deliveryCity,
                "billingCompany"=> $row->billingCompany,
                "billingLastName"=> $row->billingLastName,
                "billingFirstName"=> $row->billingFirstName,
                "deliveryLastName"=> $row->deliveryLastName,
                "modifiedDate"=> $row->modifiedDate,
                "deliveryFirstName"=> $row->deliveryFirstName,
                "dispatchedDate"=> $row->dispatchedDate,
                "invoiceDate"=> $row->invoiceDate,
                "invoiceNumber"=> $row->invoiceNumber,
                "lineItems"=> $lineItemsArr,
               ];
        }
       
        
      //  Log::info("saleorderArr" ,[$saleOrdersToAdd]);
       
        foreach ($saleOrdersToAdd as $saleOrder) {
            
            
            //check if country name is longer then 2 characters/if its a full name then get country code for the same.
            if($saleOrder['deliveryCountry'] != ""){
                if(strlen($saleOrder['deliveryCountry']) > 2){
                    $getcountryCode = $this->getCountryCode($saleOrder['deliveryCountry']);
                    if($getcountryCode){
                        $saleOrder['deliveryCountry'] = $getcountryCode->iso2;
                        if($saleOrder['deliveryCountry'] == ""){
                            $saleOrder['deliveryCountry'] = "US";
                        }
                    }else{
                        $saleOrder['deliveryCountry'] = "US";
                    } 
                     
                }
            }else{
                $saleOrder['deliveryCountry'] = "US";
            }
            if($saleOrder['billingCountry'] != ""){
                if(strlen($saleOrder['billingCountry']) > 2){
                    $getcountryCode = $this->getCountryCode($saleOrder['billingCountry']); 
                    if($getcountryCode){
                        $saleOrder['billingCountry'] = $getcountryCode->iso2;
                        if($saleOrder['billingCountry'] == ""){
                            $saleOrder['billingCountry'] = "US";
                        }
                    }else{
                        $saleOrder['billingCountry'] = "US"; 
                    }
                    
                }
            }else{
                $saleOrder['billingCountry'] = "US";
            }
            $discountLined = 0;
            if($saleOrder['discountTotal'] > 0){
                $discountLined = $saleOrder['discountTotal']/count($saleOrder['lineItems']);
            }



            $line_items = [];
            if($saleOrder['lineItems']){
                foreach ($saleOrder['lineItems'] as $item) {
                   
                    array_push($line_items, [
                        'id' => $item['lineItemId'],
                        'quantity' => $item['qty'],
                        'product_identifier' => $item['productId'],
                        'description' =>  $item['name'],
                        'unit_price' => $item['unitPrice'],
                        'discount'=>$item['discount'] + $discountLined,
                        'sales_tax' =>  '0'
                    ]);
                }
            }
            
            $zipcode = $saleOrder['billingPostalCode'];
            $from_zipcode = $saleOrder['deliveryPostalCode'];
            if($zipcode ==""){
                $zipcode = $from_zipcode;
            }

            if($saleOrder['deliveryCountry'] == "US"){
                if(strlen($zipcode) > 5){
                    $zipcode =str_replace("-", "", $zipcode);
                    $zipcode =str_replace(" ", "", $zipcode);
                    $zipcode = substr($zipcode, 0,5); 
                }
                if(strlen($from_zipcode) > 5){
                    $from_zipcode =str_replace("-", "", $from_zipcode);
                    $from_zipcode =str_replace(" ", "", $from_zipcode);
                    $from_zipcode = substr($from_zipcode, 0,5); 
                }
            }
            
            // if(isset($saleOrder['deliveryCountry'])){
            //     //Log::info("delivery-country ",[$saleOrder['deliveryCountry']]);
            // }
            // Log::info("delivery-zipcode ".$saleOrder['orderId']." >> ".$zipcode);
            // Log::info("delivery-zipcode ".$saleOrder['orderId']." >> ".$from_zipcode);
                   
          
            if (strlen($zipcode) < 5) {
                $zipcode =  '0' . $zipcode;
            }
            if (strlen($from_zipcode) < 5) {
                $from_zipcode =  '0' . $from_zipcode;
            }
            
            // //validate zipcode code. 

            $deliveryState =  $saleOrder['deliveryState'] !="" ?  $saleOrder['deliveryState']:"FL";
            $billingState = $saleOrder['billingState'] !="" ?  $saleOrder['billingState']:"FL";
            if(strlen($billingState) > 2){
                $billingStateCode = $this->getStateCode($billingState);
               
                if(empty($billingStateCode)){
                    $billingState = "FL";
                }else{
                    $billingState = $billingStateCode->iso2;
                }
            }

            if(strlen($deliveryState) > 2){
                $deliveryStateCode = $this->getStateCode($deliveryState);
                if(empty($deliveryStateCode)){
                    $deliveryState = "FL";
                }else{
                    $deliveryState = $deliveryStateCode->iso2;
                }
               
            }

        
           
           

            $amount = $saleOrder['total'];
            if($saleOrder['taxStatus'] == "Exempt" || $saleOrder['taxStatus'] == "Incl"){
                $taxRate = 0;
            }else{               
                $taxRate  = ($saleOrder['productTotal'] * $saleOrder['taxRate']);
                $amount = $saleOrder['productTotal'];
                if($saleOrder['discountTotal'] > 0){
                    $amount = $saleOrder['productTotal']-$saleOrder['discountTotal'];

                }
                if($saleOrder['freightTotal'] > 0){
                    $amount = $amount + $saleOrder['freightTotal'];
                }
                //round(($saleOrder->total -$saleOrder->productTotal-$shipping),2);
            }
            
            if($saleOrder['invoiceDate'] != NULL && $saleOrder['dispatchedDate'] != NULL){
               
                $new_transaction = [
                    'transaction_id' =>   $saleOrder['orderId'],
                    'transaction_date' =>  $saleOrder['modifiedDate'],
                    'from_country' =>   $saleOrder['deliveryCountry'] !="" ?  $saleOrder['deliveryCountry']:"US",
                    'from_zip' =>   $from_zipcode,
                    'from_state ' =>  $deliveryState ,
                    'from_city' =>   $saleOrder['deliveryCity'],
                    'from_street' =>   $saleOrder['deliveryAddress1'],
                    'to_country' =>  $saleOrder['billingCountry'] !="" ?  $saleOrder['billingCountry']:"US",
                    'to_zip' =>    $zipcode,
                    'to_state' => $billingState,
                    'to_city' =>   $saleOrder['billingCity'],
                    'to_street' =>  $saleOrder['billingAddress1'],
                    'amount' => $amount,
                    'shipping' => $saleOrder['freightTotal'],
                    'sales_tax' => $taxRate,
                    'line_items' => $line_items,
                    'provider' => 'api',
                ];
                //echo "<pre>";print_r($new_transaction);die;
                DB::table("sales_orders")->where('orderId',$saleOrder['orderId'])->update(['is_processed'=>'1']);
                try{
                    //$this->taxJarService->deleteTransaction('92071');
                    $this->taxJarService->createTransaction($new_transaction);
                    //Log::info("new inserted order id ".$saleOrder->orderId);
                } catch (Exception $e){
                    DB::table("error_logs")->insert(['orderid'=>$new_transaction['transaction_id'],"postedDate"=>date('Y-m-d'),"error"=>$e->getMessage()]);
                   // Log::info('Transaction Creation Failed: ' . $new_transaction['transaction_id']);
                   // Log::info($new_transaction);
                   // Log::info($e);
                    dump($e->getMessage());
                }
            }else{
                DB::table("sales_orders")->where('orderId',$saleOrder['orderId'])->update(['is_processed'=>'1']);
                DB::table("error_logs")->insert(['orderid'=>$saleOrder['orderId'],"postedDate"=>date('Y-m-d'),"error"=>"information from cin7 is sending NULL for: invoiceDate " .$saleOrder['invoiceDate']." & dispatchedDate ".$saleOrder['dispatchedDate']]);
            }          
        }  
    }

    /*get state code by state name */
    public function getStateCode($state_name){
        return DB::table("states")->where('name', 'like', '%' . $state_name . '%')->first();
    }
    /*Get country codes by country name*/
    public function getCountryCode($country_name){
        return DB::table("countries")->where(['name'=>$country_name])->first();
    }

    public function test_1()
    {
        $taxjarservices = new TaxJarService();
        return   $taxjarservices ->getTransactionIds('2015/05/15');
    }

    public function create_user()
    {

        $syncService = new SyncService();
        $syncService->syncCustomers();
    }
    public function delete_order()
    {
        $syncService = new SyncService();
        $syncService->deleteTransaction();
    }
}
