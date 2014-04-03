<?php
    include("../../../dbconnect.php");
    include("../../../includes/functions.php");
    include("../../../includes/gatewayfunctions.php");

    $moduleName = "gocoin";
    $GATEWAY = getGatewayVariables($moduleName);

    $coin_currency = isset($_GET['paytype']) && !empty($_GET['paytype'])?$_GET['paytype']:'' ;
    $file = __DIR__.'/gocoinlib/src/GoCoin.php';
    $json   = array();
   if(file_exists($file)){
       include_once($file);
          $options = array(
            'price_currency'        => isset($_GET['paytype'])  &&! empty($_GET['paytype'])?$_GET['paytype']:'',
            'base_price'            => isset($_REQUEST['base_price'] )&& !empty($_REQUEST['base_price'])?$_REQUEST['base_price']:'', 
            'base_price_currency'   => "USD" ,
            'notification_level'    => "all",
            'callback_url'          => isset($_REQUEST['callback_url'] )&& !empty($_REQUEST['callback_url'])?$_REQUEST['callback_url']:'', 
            'redirect_url'          => isset($_REQUEST['redirect_url'] )&& !empty($_REQUEST['redirect_url'])?$_REQUEST['redirect_url']:'', 
            'order_id'              => isset($_REQUEST['order_id'] )&& !empty($_REQUEST['order_id'])?$_REQUEST['order_id']:'', 
            'customer_name'         => isset($_REQUEST['customer_name'] )&& !empty($_REQUEST['customer_name'])?$_REQUEST['customer_name']:'',
            'customer_address_1'    => isset($_REQUEST['customer_address_1'] )&& !empty($_REQUEST['customer_address_1'])?$_REQUEST['customer_address_1']:'',
            'customer_address_2'    => isset($_REQUEST['customer_address_2'] )&& !empty($_REQUEST['customer_address_2'])?$_REQUEST['customer_address_2']:'',
            'customer_city'         => isset($_REQUEST['customer_city'] )&& !empty($_REQUEST['customer_city'])?$_REQUEST['customer_city']:'',
            'customer_region'       => isset($_REQUEST['customer_region'] )&& !empty($_REQUEST['customer_region'])?$_REQUEST['customer_region']:'',
            'customer_postal_code'  => isset($_REQUEST['customer_postal_code'] )&& !empty($_REQUEST['customer_postal_code'])?$_REQUEST['customer_postal_code']:'',
            'customer_country'      => isset($_REQUEST['customer_country'] )&& !empty($_REQUEST['customer_country'])?$_REQUEST['customer_country']:'',
            'customer_phone'        => isset($_REQUEST['customer_phone'] )&& !empty($_REQUEST['customer_phone'])?$_REQUEST['customer_phone']:'',
            'customer_email'        => isset($_REQUEST['customer_email'] )&& !empty($_REQUEST['customer_email'])?$_REQUEST['customer_email']:'',
        );
          
      $access_token =  isset($GATEWAY['module_payment_gocoin_access_token'])&& !empty($GATEWAY['module_payment_gocoin_access_token'])?$GATEWAY['module_payment_gocoin_access_token']:'';  
      $gocoin_url   =   'https://gateway.gocoin.com/merchant/';   
       if (empty($access_token)) {
            $result = 'error';
            $json['error'] = 'GoCoin Payment Paramaters not Set. Please report this to Site Administrator.';

        } else {
        
            try {
                $user = GoCoin::getUser($access_token);

                if ($user) {
                    $merchant_id = $user->merchant_id;
                    if (!empty($merchant_id)) {
                        
                        $invoice = GoCoin::createInvoice($access_token, $merchant_id, $options);
                          $json['err2']= $invoice ;
                        $json['error'] = $invoice ;
                        
                        if (isset($invoice->errors)) {
                            $result = 'error';
                            $json['error'] = 'GoCoin does not permit';
                        } elseif (isset($invoice->error)) {
                            $result = 'error';
                            $json['error'] = $invoice->error;
                        } elseif (isset($invoice->merchant_id) && $invoice->merchant_id != '' && isset($invoice->id) && $invoice->id != '') {
                            $url = $gocoin_url . $invoice->merchant_id . "/invoices/" . $invoice->id;
                            $result = 'success';
                            $messages = 'success';
                            $json['success'] = 'success';
                            $json['redirect'] = $url;
                        }
                    }
                } else {
                    $result = 'error';
                    $json['error'] = 'GoCoin Invalid Settings';
                }
            } catch (Exception $e) {
                $result = 'error';
                $json['error'] = 'GoCoin does not permit';
            }
        }
   }
   else{
         $json['error'] = 'GoCoin Payment Paramaters not Set. Please report this to Site Administrator.';
   }
   echo json_encode($json);
?>