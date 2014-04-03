<?php
function gocoin_config() {
 global $CONFIG;

    $baseUrl = $CONFIG['SystemURL'];

    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value" => "Gocoin Payments"),
        "module_payment_gocoin_client_id" => array("FriendlyName" => "Client Id", "Type" => "text", "Size" => "50",),
        "module_payment_gocoin_sequrity_key" => array("FriendlyName" => "Security Key", "Type" => "text", "Size" => "50",),
        "module_payment_gocoin_access_token" => array("FriendlyName" => "Access Token", "Type" => "text", "Size" => "50",),
        "create_token" => array(
            "FriendlyName" => "Create Token",
            "Type" => "hidden",
            "Description" => create_gocoin_token($baseUrl),
            "Size" => "20"),
    );

    gocoin_activate(); //this will create a table;

    return $configarray;
}
 
function gocoin_link($params) {

    if (!isset($_SESSION["uid"]) && !isset($_SESSION['adminid'])) {
        redirSystemURL("", "clientarea.php");
    }
    $callback_url       = $params['systemurl'] . '/modules/gateways/callback/gocoin.php';
    $success_return_url = $params['returnurl'];
     
    $firstname = $params['clientdetails']['firstname'];
    $lastname  = $params['clientdetails']['lastname'];
    $customer  = $firstname . ' ' . $lastname;
    $email     = $params['clientdetails']['email'];
    $address1  = $params['clientdetails']['address1'];
    $address2  = $params['clientdetails']['address2'];
    $city      = $params['clientdetails']['city'];
    $state     = $params['clientdetails']['state'];
    $postcode  = $params['clientdetails']['postcode'];
    $country   = $params['clientdetails']['country'];
    $phone     = $params['clientdetails']['phonenumber'];

    $cart_Gocoin_ID = isset($params['invoicenum']) ?$params['invoicenum']:$params['invoiceid'] ;
    $order_id = $cart_Gocoin_ID;

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

function gocoin_activate() {

    $query = "CREATE TABLE IF NOT EXISTS `gocoin_ipn` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                    `order_id` int(10) unsigned DEFAULT NULL,
                    `invoice_id` varchar(200) NOT NULL,
                    `url` varchar(400) NOT NULL,
                    `status` varchar(100) NOT NULL,
                    `btc_price` decimal(16,8) NOT NULL,
                    `price` decimal(16,8) NOT NULL,
                    `currency` varchar(10) NOT NULL,
                    `currency_type` varchar(10) NOT NULL,
                    `invoice_time` datetime NOT NULL,
                    `expiration_time` datetime NOT NULL,
                    `updated_time` datetime NOT NULL,
                    PRIMARY KEY (`id`)
                  );";
    return $result = mysql_query($query);
}

function create_gocoin_token($baseUrl) {

        $str = '<b>you can click button to get access token from gocoin.com</b><input type="button" value="Get API TOKEN" onclick="return get_api_token(this.form);">';
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
                    var currentUrl =  base+ "/modules/gateways/gocoin/create_token.php";
                     alert(currentUrl);
                    var url = "https://dashboard.gocoin.com/auth?response_type=code"
                                + "&client_id=" + client_id
                                + "&redirect_uri=" + currentUrl
                                + "&scope=user_read+merchant_read+invoice_read_write";
                    var strWindowFeatures = "location=yes,height=570,width=520,scrollbars=yes,status=yes";
                    var win = window.open(url, "_blank", strWindowFeatures);
                    return false;
                }</script>';
        return $str;
    }
 
?>