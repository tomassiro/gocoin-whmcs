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
                  "module_payment_gocoin_client_id" => array("FriendlyName" => "Client Id", "Type" => "text", "Size" => "50",),
                  "module_payment_gocoin_sequrity_key" => array("FriendlyName" => "Security Key", "Type" => "text", "Size" => "50",),
                  "module_payment_gocoin_access_token" => array("FriendlyName" => "Access Token", "Type" => "text", "Size" => "50",),
                  "create_token" => array(
                                        "FriendlyName" => "Create Token",
                                        "Type" => "hidden",
                                        "Description" => create_gocoin_token($baseUrl,$GATEWAY),
                                        "Size" => "20"
                                    ),   
                   );   
       }

        gocoin_activate();
        return $configarray;
    }

    function gocoin_link($params) {

        if (!isset($_SESSION["uid"]) && !isset($_SESSION['adminid'])) {
            redirSystemURL("", "clientarea.php");
        }
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

        $options = array(
            'base_price'            => $params['amount'],
            'base_price_currency'   => "USD", //$order_info['currency_code'],
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
            'customer_email'        => $email
        );

        $data = json_encode($options);

        //$code = '<form method="POST" action="modules/gateways/gocoin/order_process.php">';

        $code .= '<div>Please Select Payment Type : ';
        $code .= '<select name="paytype"  id="paytype">
                        <option value="BTC">Bitcoin</option>
                        <option value="XDG">Dogecoin</option>
                        <option value="LTC">Litecoin</option>
                  </select>';

        $code .= '<input type="button" value="Go" id="gocoin_buttion"><input type="hidden" id="g_data" value="'.$data.'"></div>';
        $code .= '<script language="javascript">
    $( "#gocoin_buttion" ).click(function() {
      var paytype = $( "#paytype" ).val();
     var url  ="modules/gateways/gocoin/order_process.php?paytype="+paytype;
     var data  =$("#g_data").val();
     $("img").each(function(index) {    
        $(this).show();
     });
        $.ajax({
          type: "POST",
          url: url,
          data: '. $data.',
          dataType: "json",
                 success: function(json) {
                $(".warning, .error").remove();

                if (json["redirect"]) {
                    location = json["redirect"];
                } else if (json["error"]) {
                     alert("Order is Cancel "+ json["error"]);     
                      window.location.href="cart.php";
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                 window.location.href="cart.php";
            }
        });

    });
    </script>';
    $code.="<script language='javascript'>

            $(document).ready(function() {
                   $('.alert.alert-block.alert-warn > p').html('Payment Type' );
            });


     $('img').each(function(index) {    
        if (this.src == '".$params['systemurl']."/images/loading.gif'){
            $(this).hide(); 
        } else {
            $(this).show();
        }
     });
    </script>";
        return $code;
    }

    // For creating gocoin_ipn table
    function gocoin_activate() {
        $query = "CREATE TABLE IF NOT EXISTS `gocoin_ipn` (
                        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `order_id` int(10) unsigned DEFAULT NULL,
                        `invoice_id` varchar(200) NOT NULL,
                        `url` varchar(200) NOT NULL,
                        `status` varchar(100) NOT NULL,
                        `btc_price` decimal(16,8) NOT NULL,
                        `price` decimal(16,8) NOT NULL,
                        `currency` varchar(10) NOT NULL,
                        `currency_type` varchar(10) NOT NULL,
                        `invoice_time` datetime NOT NULL,
                        `expiration_time` datetime NOT NULL,
                        `updated_time` datetime NOT NULL,
                        `fingerprint` varchar(250) NOT NULL,
                        PRIMARY KEY (`id`)
                      );";

         $result = mysql_query($query);
         return $result;
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
    function create_gocoin_token($baseUrl,$GATEWAY) {

    $client_id      = isset($GATEWAY['module_payment_gocoin_client_id']) && !empty($GATEWAY['module_payment_gocoin_client_id'])?$GATEWAY['module_payment_gocoin_client_id']:'';
    $client_secret      = isset($GATEWAY['module_payment_gocoin_sequrity_key']) && !empty($GATEWAY['module_payment_gocoin_sequrity_key'])?$GATEWAY['module_payment_gocoin_sequrity_key']:'';

            $str = '<input type="hidden" id="cid"  value="'.$client_id.'"/>
                    <input type="hidden" id="csec" value="'.$client_secret.'"/><b>you can click button to get access token from gocoin.com</b><input type="button" value="Get API TOKEN" onclick="return get_api_token(this.form);">';
            $str.= '<script type="text/javascript">
                var base ="' . $baseUrl . '";
                function get_api_token(obj)    
                {
                        var client_id = "";
                         var client_secret ="";
                            var elements = obj.elements;
                            for (i=0; i<elements.length; i++){
                                if(elements[i].name=="field[module_payment_gocoin_client_id]"){
                                    client_id = elements[i].value;
                                }
                                if(elements[i].name=="field[module_payment_gocoin_sequrity_key]"){
                                    client_secret =  elements[i].value;
                                }

                            }

                        if (!client_id) {
                            //alert("Please input "+mer_id+" !");
                            alert("Please input  Client Id !");
                            return false;
                        }
                        if (!client_secret) {
                           // alert("Please input "+access_key+" !");
                            alert("Please input Client Secret Key !");
                            return false;
                        }


                        var cid = document.getElementById("cid").value;
                        var csec = document.getElementById("csec").value;
                        if (client_id != cid || client_secret != csec) {
                           alert("Please save changed Client Id and Client Secret Key first!");
                           return;
                        }

                        var currentUrl =  base+ "/modules/gateways/gocoin/create_token.php";
                        //   alert(currentUrl);
                        var url = "https://dashboard.gocoin.com/auth?response_type=code"
                                    + "&client_id=" + client_id
                                    + "&redirect_uri=" + currentUrl
                                    + "&scope=user_read+invoice_read_write";
                        var strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
                        var win = window.open(url, "_blank", strWindowFeatures);
                        return false;
                    }</script>';
            return $str;
        }
 
?>