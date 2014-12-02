<?php
    function gocoin_config() {
     global $CONFIG;
        $baseUrl        = $CONFIG['SystemURL'];
        $_moduleName    ='gocoin';
        $GATEWAY=array();    
        $GATEWAY['module_payment_gocoin_client_id']     =gocoinConfigvalue($_moduleName,'module_payment_gocoin_client_id');
        $GATEWAY['module_payment_gocoin_sequrity_key']  =gocoinConfigvalue($_moduleName,'module_payment_gocoin_sequrity_key');

        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
              $php_version_allowed = true ;
       }
       else{
              $php_version_allowed = false ;
       }

       if($php_version_allowed ==false){
           changeDisplayStatus($_moduleName);
               $configarray = array(
                  "FriendlyName" => array("Type" => "System", "Value" => "GoCoin"),
                  "create_token" => array(
                                        "FriendlyName" => "",
                                        "Type" => "hidden",
                                        "Description" => "<div style='color:#ff0000;font-weight:bold;'>
                                                           PHP Version Error: The minimum PHP version required for GoCoin plugin is 5.3.0
                                                          </div>".'<style>#Payment-Gateway-Config-gocoin input[type="submit"]{display:none;}</style>',
                                        "Size" => "20"
                                    ),   
                   );   
       }
       else{
                $configarray = array(
                  "FriendlyName" => array("Type" => "System", "Value" => "GoCoin"),
                  "module_payment_gocoin_client_id" => array("FriendlyName" => "Merchant Id", "Type" => "text", "Size" => "50",),
                  "module_payment_gocoin_sequrity_key" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "50",),
                  );   
       }
 
        return $configarray;
    }

    function gocoin_link($params) { 
        $link = $params['systemurl'] . '/cart.php?a=view'; 
        if (!isset($_SESSION["uid"]) && !isset($_SESSION['adminid'])) {
            redirSystemURL("", "clientarea.php");
        }
        $file = __DIR__ . '/gocoin/gocoinlib/src/GoCoin.php';
        $json = array();
        if(!file_exists($file)) {
           logTransaction("GoCoin",'',"GoCoin Php lib not found");
           redirSystemURL("a=view", "cart.php"); 
        }
        else{
            include_once($file); 
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                   $php_version_allowed = true ;
            }
            else{
                   $php_version_allowed = false ;
            }
            $access_token = isset($params['module_payment_gocoin_sequrity_key'])?$params['module_payment_gocoin_sequrity_key']:'';
            $merchant_id = isset($params['module_payment_gocoin_client_id'])?$params['module_payment_gocoin_client_id']:'';
            if( $php_version_allowed == false){
               $msg= "The minimum PHP version required for GoCoin plugin is 5.3.0";
               
               logTransaction("GoCoin",'',$msg); 
               redirSystemURL("a=view", "cart.php"); 
            }
             // Check to make sure we have an access token (API Key)
             elseif (empty($access_token)) {
                  $msg = 'Improper Gateway set up. API Key not found.';
                  logTransaction("GoCoin",'',$msg);
                redirSystemURL("a=view", "cart.php"); 
              }
              //Check to make sure we have a merchant ID
              elseif (empty($merchant_id)) {
                  $msg = 'Improper Gateway set up. Merchant ID not found.';
                  logTransaction("GoCoin",'',$msg);
                  redirSystemURL("a=view", "cart.php"); 
              }
              else{
                   $callback_url       = $params['systemurl'] . '/modules/gateways/callback/gocoin.php';
                    $success_return_url = $params['returnurl'];

                    $firstname           = $params['clientdetails']['firstname'];
                    $lastname            = $params['clientdetails']['lastname'];
                    $customer            = $firstname . ' ' . $lastname;
                    $email               = $params['clientdetails']['email'];
                    $address1            = $params['clientdetails']['address1'];
                    $address2            = $params['clientdetails']['address2'];
                    $city                = $params['clientdetails']['city'];
                    $state               = $params['clientdetails']['state'];
                    $postcode            = $params['clientdetails']['postcode'];
                    $country             = $params['clientdetails']['country'];
                    $phone               = $params['clientdetails']['phonenumber'];

                    $cart_Gocoin_ID      = $params['invoiceid'];
                    $order_id            = $cart_Gocoin_ID;
                    if (empty($order_id)) {
                        $msg = 'Order ID not found.';
                        logTransaction("GoCoin",'',$msg);
                        redirSystemURL("", "cart.php?a=view"); 
                    }
                    $options = array(
                     "type"                     => 'bill',
                     'base_price'            => $params['amount'],
                     'base_price_currency'   => $params['currency'],
                     'notification_level'    => "all",
                     'callback_url'          => $callback_url,
                     'redirect_url'          => $success_return_url,
                     'order_id'              => $order_id,
                     'customer_name'         => $customer,
                     'customer_address_1'    => $address1,
                     'customer_address_2'    => $address2,
                     'customer_city'         => $city,
                     'customer_region'       => $state,
                     'customer_postal_code'  => $postcode,
                     'customer_country'      => $country,
                     'customer_phone'        => $phone,
                     'customer_email'        => $email);
                    $signature =  sign($options, $access_token) ;
                    $options['user_defined_8'] = $signature;
                    
                    try {
                      $gocoin_current_session = $order_id."_gocoin";
                      if(!isset($_SESSION[$gocoin_current_session])){
                        $invoice = GoCoin::createInvoice($access_token, $merchant_id, $options);
                        $url = $invoice->gateway_url;
                        if (isset($_SESSION["cart"])) {
                           unset($_SESSION["cart"]);
                        }
                        $_SESSION[$gocoin_current_session]=$invoice->gateway_url; 
                        if(isset($_SESSION[$gocoin_current_session])){
                            $code ='<div id="submitfrm"><form id="paymentfrm" name="paymentfrm" method="get" action="'.$url.'">';
                            $code.='<a href="'.$url.'">Pay with GoCoin</a>';          
                            $code.='</form></div>';
                            return $code;
                        }
                        
                      }
                      else{
                            if(isset($_SESSION[$gocoin_current_session])){
                             $url=    $_SESSION[$gocoin_current_session];
                            $code ='<div id="submitfrm"><form id="paymentfrm" name="paymentfrm" method="get" action="'.$url.'">';
                            $code.='<a href="'.$url.'">Pay with GoCoin</a>';          
                            $code.='</form></div>';
                            return $code;
                        }
                      }
                      
                  } catch (Exception $e) {
                    $msg = $e->getMessage();
                     logTransaction("GoCoin",'',$msg);
                     display_error_redirect($msg, $link);
                     redirSystemURL("a=view", "cart.php"); 
                  }
              }
        }
            
        
    }

   

    // For Fetching GoCoin Value
    function gocoinConfigvalue($_moduleName,$setting) {
        $table  = "tblpaymentgateways";
        $fields = "value";
        $where  = array("gateway" =>$_moduleName,'setting'=>$setting);
        $result = select_query($table, $fields, $where);
        $data   = mysql_fetch_array($result);
        if (is_array($data)) {
          if(isset($data['value']) )
          {
            return $data['value'];
          }
        }
    }

    // change visable status off if PHP version less then 5.3.0
    function changeDisplayStatus($module) {
        $table = "tblpaymentgateways";
        $update = array("value" =>'' );
        $where = array("setting" =>'visible', "gateway" => $module);
        update_query($table, $update, $where);
    }

    // For Create Token
     
    function sign($data, $key){
    //  $include = array('price_currency','base_price','base_price_currency','order_id','customer_name');
      $include = array('base_price','base_price_currency','order_id','customer_name');
      // $data must be an array
      if(is_array($data)) {

        $querystring = "";
        while(count($include) > 0) {
          $k = $include[0];
          if (isset($data[$k])) {
            $querystring .= $k . "=" . $data[$k] . "&";
            array_shift($include);
          }
          else {
            return false;
          }
        }

        //Strip trailing '&' and lowercase 
        $msg = substr($querystring, 0, strlen($querystring) - 1);
        $msg = strtolower($msg);

        // hash with key
        $hash = hash_hmac("sha256", $msg, $key, true);
        $encoded = base64_encode($hash);
        return $encoded;
      }
      else {
        return false;
      }
  }
  
    function display_error_redirect($msg,$link){
        echo '<script> var msg = "'.$msg.'";   alert(msg);  window.location.href = "'.$link.'"; </script>';
        die();
    }
?>