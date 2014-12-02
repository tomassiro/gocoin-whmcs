<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");



//include("../gocoinlib/validation.php");
//
 //==========Response  function ==========================//                  
 function postData() {
      //get webhook content
      $response = new stdClass();
      $post_data = file_get_contents("php://input"); 
       if (!$post_data) {
        $response->error = 'Request body is empty';
      } 
      $post_as_json = json_decode($post_data);
      if (is_null($post_as_json)){
        $response->error = 'Request body was not valid json';
      } else {
        $response = $post_as_json;
      }
      return $response;
  }
//===============Callback Function ===
function _paymentStandard() {
    $data = postData();
    $moduleName = "gocoin";
    $GATEWAY = getGatewayVariables($moduleName);
    $adminuser = $GATEWAY['whmcs_admin_username'];
    //var_dump($adminuser);
    // check Module is  active 
    if (!$GATEWAY["type"]){
        logTransaction("GoCoin",'','Module Not Activated');
    }
    // check API Key 
     $key                =   isset($GATEWAY['module_payment_gocoin_sequrity_key'])?$GATEWAY['module_payment_gocoin_sequrity_key']:'';
     if(empty($key)){
        $msg=  'Api Key is  blank';
        logTransaction("GoCoin",'',$msg);
       
     }   
     
     if (isset($data->error)){
        logTransaction("GoCoin",'',$data->error);
      }
      else { 
        $event_id           = $data -> id;
        $event              = $data -> event;
        $invoice            = $data -> payload;
        $payload_arr        = get_object_vars($invoice) ;
                 ksort($payload_arr);
        $signature          = $invoice -> user_defined_8;
        
        $sig_comp           = sign($payload_arr, $key);
        $status             = $invoice -> status;
        $order_id           = $invoice -> order_id;
        $transction_id      = $invoice->id;
        $price              = $invoice->base_price;
        
            // Check that if a signature exists, it is valid 
        if (isset($signature) && ($signature != $sig_comp)) {
            $msg = "Signature : " . $signature . "does not match for Order: " . $order_id ."$sig_comp        |    $signature ";
            $msg .= ' Event ID: '. $event_id;
            logTransaction("GoCoin",'',$msg);
           
        }
        elseif (empty($signature) || empty($sig_comp) ) {
            $msg = "Signature is blank for Order: " . $order_id;
            $msg .= ' Event ID: '. $event_id;
            logTransaction("GoCoin",'',$msg);
           
        }
        elseif($signature == $sig_comp) {
          $value = array();
          switch($event) {

            case 'invoice_created':
              break;

            case 'invoice_payment_received':
              switch ($status) {
                 case 'ready_to_ship':
                  $msg = 'Invoice ' . $order_id .' is paid and awaiting payment confirmation on blockchain.';
                  break; 
                case 'paid':
                  $msg = 'Invoice ' . $order_id .' is paid and awaiting payment confirmation on blockchain.';
                 
                  break;
                case 'underpaid':
                  $msg = 'Invoice ' . $order_id .' is underpaid.';
                 
                  break;
              }
              $msg .=" Price (Currency)  : ".  $invoice->price."(". $invoice->price_currency.")"; 
              $msg .= ' Event ID: '. $event_id; 
              logTransaction("GoCoin",'',$msg);
             
              break;

            case 'invoice_merchant_review':
              $msg = 'Invoice ' . $order_id .' is under review. Action must be taken from the GoCoin Dashboard.';
              $msg .=" Price (Currency)  : ".  $invoice->price."(". $invoice->price_currency.")"; 
              $msg .= ' Event ID: '. $event_id;
                 
              logTransaction("GoCoin",'',$msg);
             
              
              break;
            case 'invoice_ready_to_ship':
              $msg = 'Invoice ' . $order_id .' has been paid in full and confirmed on the blockchain.';
              $msg .=" Price (Currency)  : ".  $invoice->price."(". $invoice->price_currency.")"; 
              $msg .= ' Event ID: '. $event_id;
              
              logTransaction("GoCoin",'',$msg);
                $fdata = checkAlreadyLog($transction_id);
                if(empty($fdata)){  
                    addInvoicePayment($order_id, $transction_id, $price, 0, $moduleName);
                } 
             
              
              break;

            case 'invoice_invalid':
              $msg = 'Invoice ' . $order_id . ' is invalid and will not be confirmed on the blockchain.';
              $msg .=" Price (Currency)  : ".  $invoice->price."(". $invoice->price_currency.")"; 
              $msg .= ' Event ID: '. $event_id;
              $command = "updateinvoice";
              $values["invoiceid"] = $order_id; #changeme
              $values["notes"] = $msg; #changeme
              $results = localAPI($command, $values, $adminuser);
              logTransaction("GoCoin",'',$msg);
             
              
              break;

            default: 
              
              $msg = "Unrecognized event type: ". $event;
              $msg .= ' Event ID: '. $event_id;
              logTransaction("GoCoin",'',$msg);
             
              
                
          }
            
        }
        
      }
     
                 
}

//===============Check To Avoid duble transction entry already Log ===
function checkAlreadyLog($invoice){
    $table  = "tblaccounts";
    $fields = "transid";
    $where  = array("transid" => $invoice,"gateway"=>'gocoin');
    $result = select_query($table, $fields, $where);
    $data = mysql_fetch_array($result);
    if (is_array($data) && isset($data['transid'])) {
        return $data['transid'];
    }
    else{
       return '' ;
    }
}

_paymentStandard();
?>