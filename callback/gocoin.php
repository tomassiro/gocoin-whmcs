<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
//include("../gocoinlib/validation.php");

function getNotifyData() {
    $post_data = file_get_contents("php://input");
    if (!$post_data) {
        $response = new stdClass();
        $response->error = 'Post Data Error';
        return $response;
    }
    $response = json_decode($post_data);
    return $response;
}

function _paymentStandard() {
  $response = getNotifyData();
	$moduleName = "gocoin";
	$GATEWAY = getGatewayVariables($moduleName);

	if (!$GATEWAY["type"]) die("Module Not Activated");

	if ( !isset($_SESSION["uid"]) && !isset($_SESSION['adminid']) ) {
	//redirSystemURL("", "clientarea.php");
	}
	$adminuser = $GATEWAY['whmcs_admin_username'];

  $error = 0;
    if (!$response) {
        $error = $error +1;
        $error_msg[] = ' NotifyData Blank';
    
    }
    if (isset($response->error) && $response->error != '') {
        $error = $error +1;
        $error_msg[] = $response->error;
    }
    if (isset($response->payload)) {
        //======================IF Response Get=============================     
        $event                      = $response->event;
        $order_id                   = (int) $response->payload->order_id;
        $redirect_url               = $response->payload->redirect_url;
        $transction_id              = $response->payload->id;
        $total                      = $response->payload->base_price;
        $status                     = $response->payload->status;
        
        $currency                   = $response->payload->base_price_currency;
        $currency_type              = $response->payload->price_currency;
        $invoice_time               = $response->payload->created_at;
        $expiration_time            = $response->payload->expires_at;
        $updated_time               = $response->payload->updated_at;
        $merchant_id                = $response->payload->merchant_id;
        $btc_price                  = $response->payload->price;
        $price                      = $response->payload->base_price;
        $url                        = "https://gateway.gocoin.com/merchant/" . $merchant_id . "/invoices/" . $transction_id;
            
        
        //=================== Set To Array=====================================//
        //Used for adding in db
        $iArray = array(
            'order_id'      => $order_id,
            'invoice_id'    => $transction_id,
            'url'           => $url,
            'status'        => $event,
            'btc_price'     => $btc_price,
            'price'         => $price,
            'currency'      => $currency,
            'currency_type' => $currency_type,
            'invoice_time'  => $invoice_time,
            'expiration_time'=> $expiration_time,
            'updated_time'   => $updated_time);

              addTransaction1('payment', $iArray);


        if(isset($order_id) && is_numeric($order_id) && ($order_id > 0))
        {
          $result = select_query("tblinvoices","",array("id"=>(int)$order_id));
          $data = mysql_fetch_array($result, MYSQL_ASSOC);
             switch ($event) {
                case 'invoice_ready_to_ship':
                    $command = "updateinvoice";
                    $values["invoiceid"] = $order_id; #changeme
                    $values["notes"] = $currency_type.":{$btc_price};USD:{$price};"; #changeme
                    $results = localAPI($command,$values,$adminuser);
                    addInvoicePayment($order_id,$transction_id,$price,0,$moduleName); 
                    $command = "addinvoicepayment";
                    $values["invoiceid"] = $order_id;
                    $values["transid"] = $transction_id;
                    $values["amount"] = $price;
                    //$values["fee"] = $fee;
                    $values["gateway"] = $GATEWAY['name'];
                    $results = localAPI($command,$values,$adminuser);
                    logTransaction($GATEWAY["name"],$json,"Successful"); # Save to Gateway Log: name, data array, status

                    break;

                default:
                    $command = "updateinvoice";
                    $values["invoiceid"] = $invoice_id; #changeme
                    $values["status"] = "Unpaid";
                    $results = localAPI($command,$values,$adminuser);
                    logTransaction($GATEWAY["name"],$json,"Canceled"); # Save to Gateway Log: name, data array, status

                    break;
            }
      }
         
    }
    


    if($error > 0){
         $email_body = @implode('<br>',$error_msg);
    }
}
function addTransaction1($type = 'payment', $details){
    return mysql_query("
      INSERT INTO gocoin_ipn (order_id, invoice_id, url, status, btc_price,
      price, currency, currency_type, invoice_time, expiration_time, updated_time)
      VALUES ( 
          '" . $details['order_id'] . "',
          '" . $details['invoice_id'] . "',
          '" . $details['url'] . "',
          '" . $details['status'] . "',
          '" . $details['btc_price'] . "',
          '" . $details['price'] . "',
          '" . $details['currency'] . "',
          '" . $details['currency_type'] . "',
          '" . $details['invoice_time'] . "',
          '" . $details['expiration_time'] . "',
          '" . $details['updated_time'] . "' )");
}
_paymentStandard();

?>