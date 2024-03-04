<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\Cin7Service;
use App\Services\TaxJarService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DB;
use Mail;
use File;
use Response;
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

       // $this->deleteTransaction();
        $this->createCSV();
        $this->sendErrorLogs();
        $this->updateDataRecords();
        $this->convertSaleOrderToTransaction();
        $this->syncCustomers();
       
    }
    /*Function for date formatting 1 day before current date*/
    public function formatedDate($date){
        $date=date_create($date);
        date_modify($date,"-1 days");
        return date_format($date,"Y-m-d");
    }

    /*Function for Creating csv*/
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
    /*Function for sending email*/
    public function sendErrorLogs(){
        $date = date("Y-m-d");
        $date = $this->formatedDate($date);
        $checkStatus = DB::table("error_logs_mail")->where(["sent_date"=>$date,"is_processed"=>"0"])->first();
        /**
         * Turned off error emails
         */
        /*if(!empty($checkStatus)){
            Mail::send('emails.test', ['date'=>$date], function ($m) use ($date) {
                $m->from('noreply@noma.dev.hpprojects.net', 'Human Pixel');
                $pathToFile = public_path("error-logs/error-log-".$date.".csv");
                $m->attach($pathToFile);
                $m->to("gunjit.singh@humanpixel.com.au", "Gunjit Singh");
                $m->cc("katie@nomaddesigntackle.com", "Katie O'Regan");
                $m->cc("belle@humanpixel.com.au", "Belle Aicken")->subject('Error logs file for '.date('d M Y',strtotime($date)));
            });
            DB::table("error_logs_mail")->where(["sent_date"=>$date])->update(["is_processed"=>1]);
        }*/
    }
    /*Function for Creating record in taxjar from database*/
    public function convertSaleOrderToTransaction()
    {

        /*update sales_orders set is_processed = -1 where orderId in (SELECT `orderid` FROM `error_logs` WHERE (`updated_at` < NOW() - INTERVAL 1 WEEK) AND `updated_at` > NOW() - INTERVAL 2 WEEK )*/

        

        $saleOrdersToAdd = [];
        $saleOrders = DB::table("sales_orders")->where('is_processed',0)->where("invoiceDate","!=",NULL)->where("dispatchedDate","!=",NULL)->orderBy('orderId','desc')->limit(50)->get();

        $amount_sales_array = [];
        Log::info("convertSaleOrderToTransaction");
        foreach ($saleOrders as $row) {
            $lineItemsArr = [];
            $lineItems = DB::table("order_line_items")->where("transactionId",$row->orderId)->get();
            $amount_sales = 0;
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

                    Log::info("discount: ".$rows->discount);
                    $amount_sales = $amount_sales + ((round($rows->unitPrice,2) * $rows->qty) - round($rows->discount,2));
                }
                
            }
            $amount_sales_array[$row->orderId] = $amount_sales;

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
                'company' => $row->company
               ];

        }
       
        
      //  Log::info("saleorderArr" ,[$saleOrdersToAdd]);
        foreach ($saleOrdersToAdd as $saleOrder) {
            
           // DB::table("sales_orders")->where('orderId',$saleOrder['orderId'])->update(['is_processed'=>'2']);
        
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
           

            
            $counter = 0;
            if($saleOrder['lineItems']){
                foreach ($saleOrder['lineItems'] as $item) {
                    if($item['qty'] > 0 && $item['unitPrice'] > 0) {
                        $counter++;
                    }
                }
            }
            if($saleOrder['discountTotal'] > 0){
                $discountLined = $saleOrder['discountTotal']/$counter;
                
            }
            
            $line_items = [];
            $is_giftcard = 0;
            $total_salesamount = $amount_sales;
            $gap = 0;
            if($saleOrder['lineItems']){
                foreach ($saleOrder['lineItems'] as $item) {
                   $newDiscount = $item['discount'];

                    if($item['qty'] > 0 && $item['unitPrice'] > 0) {
                        $newDiscount = $newDiscount + $discountLined;
                        
                        $productAmount =  $item['qty'] * $item['unitPrice'];
                        if($newDiscount > $productAmount ) {

                            $gap = $newDiscount - $productAmount + $gap;
                            $newDiscount = $productAmount;

                        }

                        $total_salesamount = $total_salesamount - $newDiscount;

                    }
                    

                    array_push($line_items, [
                        'id' => $item['lineItemId'],
                        'quantity' => $item['qty'],
                        'product_identifier' => $item['productId'],
                        'description' =>  $item['name'],
                        'unit_price' => $item['unitPrice'],
                        'discount'=> $newDiscount,
                        'sales_tax' =>  '0'
                    ]);

                    if(stripos($item['name'], "Gift Card") !== false) {
                        $is_giftcard = 1;
                    }
                    
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
            
            if(isset($saleOrder['deliveryCountry'])){
               // Log::info("delivery-country ",[$saleOrder['deliveryCountry']]);
            }
            //Log::info("delivery-zipcode ".$saleOrder['orderId']." >> ".$zipcode);
            //Log::info("delivery-zipcode ".$saleOrder['orderId']." >> ".$from_zipcode);
                   
          
            if (strlen($zipcode) < 5) {
                $zipcode =  '0' . $zipcode;
            }
            if (strlen($from_zipcode) < 5) {
                $from_zipcode =  '0' . $from_zipcode;
            }
            
            //validate zipcode code. 


            $states_array = [
                strtolower('Arizona') => 'AZ',
                strtolower('Alabama') => 'AL',
                strtolower('Alaska') => 'AK',
                strtolower('Arkansas') => 'AR',
                strtolower('California') => 'CA',
                strtolower('Colorado') => 'CO',
                strtolower('Connecticut') => 'CT',
                strtolower('Delaware') => 'DE',
                strtolower('Florida') => 'FL',
                strtolower('Georgia') => 'GA',
                strtolower('Hawaii') => 'HI',
                strtolower('Idaho') => 'ID',
                strtolower('Illinois') => 'IL',
                strtolower('Indiana') => 'IN',
                strtolower('Iowa') => 'IA',
                strtolower('Kansas') => 'KS',
                strtolower('Kentucky') => 'KY',
                strtolower('Louisiana') => 'LA',
                strtolower('Maine') => 'ME',
                strtolower('Maryland') => 'MD',
                strtolower('Massachusetts') => 'MA',
                strtolower('Michigan') => 'MI',
                strtolower('Minnesota') => 'MN',
                strtolower('Mississippi') => 'MS',
                strtolower('Missouri') => 'MO',
                strtolower('Montana') => 'MT',
                strtolower('Nebraska') => 'NE',
                strtolower('Nevada') => 'NV',
                strtolower('New Hampshire') => 'NH',
                strtolower('New Jersey') => 'NJ',
                strtolower('New Mexico') => 'NM',
                strtolower('New York') => 'NY',
                strtolower('North Carolina') => 'NC',
                strtolower('North Dakota') => 'ND',
                strtolower('Ohio') => 'OH',
                strtolower('Oklahoma') => 'OK',
                strtolower('Oregon') => 'OR',
                strtolower('Pennsylvania') => 'PA',
                strtolower('Rhode Island') => 'RI',
                strtolower('South Carolina') => 'SC',
                strtolower('South Dakota') => 'SD',
                strtolower('Tennessee') => 'TN',
                strtolower('Texas') => 'TX',
                strtolower('Utah') => 'UT',
                strtolower('Vermont') => 'VT',
                strtolower('Virginia') => 'VA',
                strtolower('Washington') => 'WA',
                strtolower('West Virginia') => 'WV',
                strtolower('Wisconsin') => 'WI',
                strtolower('Wyoming') => 'WY',
                strtolower('Armed Forces America') => 'AA',
                strtolower('Armed Forces Europe') => 'AE',
                strtolower('Armed Forces Pacific') => 'AP',
                strtolower('District of Columbia') => 'DC',
                strtolower('usvi') => 'VI',
                strtolower('ARMED FORCES AMERICAS') =>'AA'
            ];
            
            //print_r($states_array);

            $deliveryState =  $saleOrder['deliveryState'] !="" ?  $saleOrder['deliveryState']:"";
            $billingState = $saleOrder['billingState'] !="" ?  $saleOrder['billingState']:"";
            if(strlen($billingState) > 2){
                $billingStateCode = $this->getStateCode($billingState);
                if(empty($billingStateCode)){
                    if(isset($states_array[strtolower($billingState)])) {
                        $billingState = $states_array[strtolower($billingState)];
                    } else {
                        $billingState = $billingState;
                    }
                    
                }else{
                    $billingState = $billingStateCode->iso2;
                }
            }

            if(strlen($deliveryState) > 2){
                $deliveryStateCode = $this->getStateCode($deliveryState);
                if(empty($deliveryStateCode)){
                    if(isset($states_array[strtolower($deliveryState)])) {
                        $deliveryState = $states_array[strtolower($deliveryState)];
                    } else {
                        $deliveryState = $deliveryState;
                    }
                }else{
                    $deliveryState = $deliveryStateCode->iso2;
                }
               
            }

            // if($saleOrder['deliveryCountry'] != ""){
            //     $filepath = public_path("zipcodes/".$saleOrder['deliveryCountry']."/zipcodes.".strtolower($saleOrder['deliveryCountry']).".json");
            //     $data =  file_get_contents($filepath);
            //     $codeArr = json_decode($data);
            //     foreach ($codeArr as $key => $value) {
            //        $codeArray[] = $value->zipcode;
            //     }
            //     if(!in_array($zipcode, $codeArray)){
            //         DB::table("error_logs")->insert(['orderid'=>$saleOrder['orderId'],"error"=>"zipcode ".$zipcode." does not exist for the selected country."]);
            //     } 
            //     if(!in_array($zipcode, $codeArray)){
            //         DB::table("error_logs")->insert(['orderid'=>$saleOrder['orderId'],"error"=>"zipcode ".$from_zipcode." does not exist for the selected country."]);
            //     }   
            // }
           
            $shipping = $saleOrder['freightTotal'];
            if($saleOrder['freightTotal'] == $saleOrder['discountTotal']){
                $shipping = 0;
            }

            //$amount = $amount_sales ;//$saleOrder['total'];

            $amount = $saleOrder['total'] ;
            if($saleOrder['taxStatus'] == "Exempt" || $saleOrder['taxStatus'] == "Incl" || trim($saleOrder["company"]) == "Warranty FOC"){
                unset($saleOrder["company"]);
                $taxRate = 0;
                $amount = $amount_sales_array[$saleOrder['orderId']] ;
                Log::info("amount_sales");
                Log::info($amount_sales_array[$saleOrder['orderId']]);
                if($saleOrder['freightTotal'] > 0){
                    $amount = $amount + $saleOrder['freightTotal'];
                }
                if($saleOrder['discountTotal'] > 0){
                    $amount = $amount - $saleOrder['discountTotal'];

                }
            }else{               
                $taxRate  = ($amount_sales_array[$saleOrder['orderId']] * $saleOrder['taxRate']);
                $amount = $amount_sales_array[$saleOrder['orderId']];
                if($saleOrder['discountTotal'] > 0){
                    $amount = $amount_sales_array[$saleOrder['orderId']]-$saleOrder['discountTotal'];

                }
                if($saleOrder['freightTotal'] > 0){
                    $amount = $amount + $saleOrder['freightTotal'];
                }
                //round(($saleOrder->total -$saleOrder->productTotal-$shipping),2);
                Log::info("productTotal");
                Log::info($saleOrder['productTotal']);

                Log::info("amount_sales");
                Log::info($amount_sales_array[$saleOrder['orderId']]);
            }
            
            if($saleOrder['invoiceDate'] != NULL && $saleOrder['dispatchedDate'] != NULL){
                $new_transaction = [
                    'transaction_id' =>   $saleOrder['orderId'],
                    'transaction_date' =>  $saleOrder['modifiedDate'],

                    'to_country' =>   $saleOrder['deliveryCountry'] !="" ?  $saleOrder['deliveryCountry']:"US",
                    'to_zip' =>   $from_zipcode,
                    'to_state' =>  $deliveryState ,
                    'to_city' =>   $saleOrder['deliveryCity'],
                    'to_street' =>   $saleOrder['deliveryAddress1'],

                    'from_country' =>  $saleOrder['billingCountry'] !="" ?  $saleOrder['billingCountry']:"US",
                    'from_zip' =>    $zipcode,
                    'from_state' => $billingState,
                    'from_city' =>   $saleOrder['billingCity'],
                    'from_street' =>  $saleOrder['billingAddress1'],

                    'amount' => $amount,
                    'shipping' => $saleOrder['freightTotal'],
                    //'shipping' => $saleOrder['freightTotal'] - $gap, //correct code for overall discount.
                    'sales_tax' => $taxRate,
                    'line_items' => $line_items,
                    'provider' => 'api',
                ];
                
                if($is_giftcard == 1) {
                    $new_transaction["to_country"] =  $new_transaction["from_country"];
                    $new_transaction["to_zip"] =  $new_transaction["from_zip"];
                    $new_transaction["to_state"] =  $new_transaction["from_state"];
                    $new_transaction["to_city"] =  $new_transaction["from_city"];
                    $new_transaction["to_street"] =  $new_transaction["from_street"];
                }
                DB::table("sales_orders")->where('orderId',$saleOrder['orderId'])->update(['is_processed'=>'1']);
                Log::info("transactions");
                Log::info(print_r($new_transaction, true));
                try{
                  
                 
                    //$this->taxJarService->deleteTransaction('92572');
                    $apiResponse = $this->taxJarService->createTransaction($new_transaction);
                    Log::info(print_r($apiResponse, true));
                    
                } catch (Exception $e){
                    if($e->getMessage() != "422 Unprocessable Entity – Provider tranx already imported for your user account"){
                        DB::table("error_logs")->insert(['orderid'=>$new_transaction['transaction_id'],"postedDate"=>date('Y-m-d'),"error"=>$e->getMessage()]);
                        Log::info('Transaction Creation Failed: ' . $new_transaction['transaction_id']);
                        DB::table("sales_orders")->where('orderId',$saleOrder['orderId'])->update(['is_processed'=>'-1']);
                       // Log::info($new_transaction);
                        Log::info($e->getMessage());
                        //dump($e->getMessage());
                    } else {
                        Log::info($e->getMessage());
                    }
                }
            }else{
                DB::table("sales_orders")->where('orderId',$saleOrder['orderId'])->update(['is_processed'=>'-1']);
                DB::table("error_logs")->insert(['orderid'=>$saleOrder['orderId'],"postedDate"=>date('Y-m-d'),"error"=>"information from cin7 is sending NULL for: invoiceDate " .$saleOrder['invoiceDate']." & dispatchedDate ".$saleOrder['dispatchedDate']]);
            }          
        }             
    }

    /*Function for Creating record in database from cin7*/
    public function updateDataRecords()
    {

        

        $affected_hp = DB::table('sales_orders')->whereRaw('orderId in (SELECT `orderid` FROM `error_logs` WHERE (`updated_at` > NOW() - INTERVAL 6 DAY) AND `updated_at` < NOW() - INTERVAL 1 WEEK ) AND is_processed = -1')
              ->update(['is_processed' => 0]);

        Log::info($affected_hp);


        $page_num = DB::table("cinpager")->first();
        $pageno =$page_num->page_no;
        $saleOrders1 = $this->cin7Service->fetchSalesOrder($pageno);
        $transactionIds1 = $this->taxJarService->getTransactionIds('2015/05/15');
        Log::info("saleOrders1");
        Log::info($saleOrders1);
        $saleOrdersToAdd1 = [];
        foreach ($saleOrders1 as $saleOrder1) {
            if (!in_array($saleOrder1['reference'], (array) $transactionIds1)) {

                array_push($saleOrdersToAdd1, $saleOrder1);
            }
        }
        foreach($saleOrdersToAdd1 as $saleorder1){
            $data = $this->getMainFields($saleorder1);
            $lineItems = $data['lineItems'];
            unset($data['lineItems']);
            DB::table('sales_orders')->updateOrInsert(["orderId"=>$data['orderId']],$data);
            foreach($lineItems as $lineItem){
                $lines = $this->getLineItemsFields($lineItem);

                DB::table('order_line_items')->updateOrInsert(["lineItemId"=>$lines['lineItemId']],$lines);
            
            }
            
        }

        if(!empty($saleOrders1)){
            $page_no = $pageno+1;
        }else{
            $page_no = 1;
        }
        
        DB::table("cinpager")->update(["page_no"=>($page_no),"updated_at"=>date("Y-m-d H:i:s")]);
     }

     public function getMainFields($data){
        $arr = [
            "orderId"=> $data['id'],
            "createdDate"=> $data['createdDate'],
             "createdBy"=> $data['createdBy'],
            "processedBy"=> $data['processedBy'],
            "isApproved"=> $data['isApproved'],
            "reference"=> $data['reference'],
            "memberId"=> $data['memberId'],
            "firstName"=> $data['firstName'],
            "lastName"=> $data['lastName'],
            "company"=> $data['company'],
            "email"=> $data['email'],
            "phone"=> $data['phone'],
            "mobile"=> $data['mobile'],
            "fax"=> $data['fax'],
            "deliveryFirstName"=> $data['deliveryFirstName'],
            "deliveryLastName"=> $data['deliveryLastName'],
            "deliveryCity"=> $data['deliveryCity'],
            "deliveryState"=> $data['deliveryState'],
            "deliveryPostalCode"=> $data['deliveryPostalCode'],
            "deliveryCountry"=> $data['deliveryCountry'],
            "billingFirstName"=> $data['billingFirstName'],
            "billingLastName"=> $data['billingLastName'],
            "billingCompany"=> $data['billingCompany'],
            "lineItems"=> $data['lineItems'],
            "billingAddress1"=> $data['billingAddress1'],
            "billingAddress2"=> $data['billingAddress2'],
            "billingCity"=> $data['billingCity'],
            "billingPostalCode"=> $data['billingPostalCode'],
            "billingState"=> $data['billingState'],
            "billingPostalCode"=> $data['billingPostalCode'],
            "billingCountry"=> $data['billingCountry'],
            "branchId"=> $data['branchId'],
            "modifiedDate"=> $data['modifiedDate'],
            "branchEmail"=> $data['branchEmail'],
            "projectName"=> $data['projectName'],
            "productTotal"=> round($data['productTotal'],2),
            "freightDescription"=> $data['freightDescription'],
            "freightTotal"=> round($data['freightTotal'],2),
            "surcharge"=> $data['surcharge'],
            "discountTotal"=> round($data['discountTotal'],2),
            "total"=> round($data['total'],2),
            "currencyCode"=> $data['currencyCode'],
            "currencyRate"=> $data['currencyRate'],
            "taxStatus"=> $data['taxStatus'],
            "taxRate"=> $data['taxRate'],
            "source"=> $data['source'],
            "isVoid"=> $data['isVoid'],
            "memberEmail"=> $data['memberEmail'],
            "isVoid"=> $data['isVoid'],
            "alternativeTaxRate"=> $data['alternativeTaxRate'],
            "invoiceDate"=> $data['invoiceDate'],
            "dispatchedDate"=> $data['dispatchedDate'],
            "status"=> $data['status'],
            "invoiceNumber"=> $data['invoiceNumber']
        ];
        return $arr;
    }

    public function getLineItemsFields($data){
            $arr = [
            "lineItemId"=> $data['id'],
            "qty"=> $data['qty'],
            "code"=> $data['code'],
            "name"=> $data['name'],
            "sort"=> $data['sort'],
            "barcode"=> $data['barcode'],
            "option1"=> $data['option1'],
            "option2"=> $data['option2'],
            "option3"=> $data['option3'],
            "discount"=> round($data['discount'],2),
            "parentId"=> $data['parentId'],
            "unitPrice"=> round($data['unitPrice'],2),
            "unitCost"=> round($data['unitCost'],2),
            "productId"=> $data['productId'],
            "styleCode"=> $data['styleCode'],
            "qtyShipped"=> $data['qtyShipped'],
            "transactionId"=> $data['transactionId'],
            "productOptionId"=> $data['productOptionId'],
            ];
            return $arr;
    }
    public function formatPrice($amount){
        return round($amount,2);
    }
    /*get state code by state name */
    public function getStateCode($state_name){
        return DB::table("states")->where('name', 'like', '%' . $state_name . '%')->first();
    }
    /*Get country codes by country name*/
    public function getCountryCode($country_name){
        return DB::table("countries")->where(['name'=>$country_name])->first();
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
            if (Str::length($adduser['firstName']) != 5) {
                $name =  'unknow';
            } elseif (Str::length($adduser['firstName']) == 5) {
                $name =  $adduser['firstName'];
            }
            $this->taxJarService->createCustomer([
                'customer_id' => $adduser['id'],
                'exemption_type' =>  'other',
                'name' => $name,
            ]);
        }
    }

    public function deleteTransaction()
    {
        $saleOrders = $this->cin7Service->fetchSalesOrder();
        $todayDate = date("Y/m/d");
        $transactionIds = $this->taxJarService->getTransactionIds('2015/05/15');
        $saleOrderIds = array_map(function ($value) {
            return $value['id'];
        }, $saleOrders);
        foreach ($transactionIds as $transactionId) {
            if (!in_array($transactionId, $saleOrderIds)) {
                //$this->taxJarService->deleteTransaction($transactionId);
            }
        }
    }
}
