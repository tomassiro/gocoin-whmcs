<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");

$moduleName = "gocoin";
$GATEWAY = getGatewayVariables($moduleName);

$coin_currency = isset($_GET['paytype']) && !empty($_GET['paytype']) ? $_GET['paytype'] : '';
$file = __DIR__ . '/gocoinlib/src/GoCoin.php';
$json = array();
if (file_exists($file)) {
    include_once($file);
    $options = array(
        'price_currency'        => isset($_GET['paytype']) && !empty($_GET['paytype']) ? $_GET['paytype'] : '',
        'base_price'            => isset($_REQUEST['base_price']) && !empty($_REQUEST['base_price']) ? $_REQUEST['base_price'] : '',
        'base_price_currency'   => "USD",
        'callback_url'          => isset($_REQUEST['callback_url']) && !empty($_REQUEST['callback_url']) ? $_REQUEST['callback_url'] : '',
        'redirect_url'          => isset($_REQUEST['redirect_url']) && !empty($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : '',
        'order_id'              => isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id']) ? $_REQUEST['order_id'] : '',
        'customer_name'         => isset($_REQUEST['customer_name']) && !empty($_REQUEST['customer_name']) ? $_REQUEST['customer_name'] : '',
        'customer_address_1'    => isset($_REQUEST['customer_address_1']) && !empty($_REQUEST['customer_address_1']) ? $_REQUEST['customer_address_1'] : '',
        'customer_address_2'    => isset($_REQUEST['customer_address_2']) && !empty($_REQUEST['customer_address_2']) ? $_REQUEST['customer_address_2'] : '',
        'customer_city'         => isset($_REQUEST['customer_city']) && !empty($_REQUEST['customer_city']) ? $_REQUEST['customer_city'] : '',
        'customer_region'       => isset($_REQUEST['customer_region']) && !empty($_REQUEST['customer_region']) ? $_REQUEST['customer_region'] : '',
        'customer_postal_code'  => isset($_REQUEST['customer_postal_code']) && !empty($_REQUEST['customer_postal_code']) ? $_REQUEST['customer_postal_code'] : '',
        'customer_country'      => isset($_REQUEST['customer_country']) && !empty($_REQUEST['customer_country']) ? $_REQUEST['customer_country'] : '',
        'customer_phone'        => isset($_REQUEST['customer_phone']) && !empty($_REQUEST['customer_phone']) ? $_REQUEST['customer_phone'] : '',
        'customer_email'        => isset($_REQUEST['customer_email']) && !empty($_REQUEST['customer_email']) ? $_REQUEST['customer_email'] : '',
    );
    $key                        = getGUID();
    $signature                  = getSignatureText($options, $key);
    $options['user_defined_8']  = $signature;

    $access_token = isset($GATEWAY['module_payment_gocoin_access_token']) && !empty($GATEWAY['module_payment_gocoin_access_token']) ? $GATEWAY['module_payment_gocoin_access_token'] : '';
    $gocoin_url = 'https://gateway.gocoin.com/merchant/';
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


                        $json_array = array(
                            'order_id'          => $invoice->order_id,
                            'invoice_id'        => $invoice->id,
                            'url'               => $url,
                            'status'            => 'invoice_created',
                            'btc_price'         => $invoice->price,
                            'price'             => $invoice->base_price,
                            'currency'          => $invoice->base_price_currency,
                            'currency_type'     => $invoice->price_currency,
                            'invoice_time'      => $invoice->created_at,
                            'expiration_time'   => $invoice->expires_at,
                            'updated_time'      => $invoice->updated_at,
                            'fingerprint'       => $signature
                        );

                        addTransaction_v1($type = 'payment', $json_array);
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
} else {
    $json['error'] = 'GoCoin Payment Paramaters not Set. Please report this to Site Administrator.';
}
echo json_encode($json);

    function getGUID() {
        if (function_exists('com_create_guid')) {
            $guid = com_create_guid();
            $guid = str_replace("{", "", $guid);
            $guid = str_replace("}", "", $guid);
            return $guid;
        } else {
            mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45); // "-"
            $uuid = substr($charid, 0, 8) . $hyphen
                    . substr($charid, 8, 4) . $hyphen
                    . substr($charid, 12, 4) . $hyphen
                    . substr($charid, 16, 4) . $hyphen
                    . substr($charid, 20, 12); // .chr(125) //"}"
            return $uuid;
        }
    }

    function getSignatureText($data, $uniquekey) {
        $query_str = '';
        $include_params = array('price_currency', 'base_price', 'base_price_currency', 'order_id', 'customer_name', 'customer_city', 'customer_region', 'customer_postal_code', 'customer_country', 'customer_phone', 'customer_email');
        if (is_array($data)) {
            ksort($data);
            $querystring = "";
            foreach ($data as $k => $v) {
                if (in_array($k, $include_params)) {
                    $querystring = $querystring . $k . "=" . $v . "&";
                }
            }
        } else {
            if (isset($data->payload)) {
                $payload_obj = $data->payload;
                $payload_arr = get_object_vars($payload_obj);
                ksort($payload_arr);
                $querystring = "";
                foreach ($payload_arr as $k => $v) {
                    if (in_array($k, $include_params)) {
                        $querystring = $querystring . $k . "=" . $v . "&";
                    }
                }
            }
        }
        $query_str = substr($querystring, 0, strlen($querystring) - 1);
        $query_str = strtolower($query_str);
        $hash2 = hash_hmac("sha256", $query_str, $uniquekey, true);
        $hash2_encoded = base64_encode($hash2);
        return $hash2_encoded;
    }

    function addTransaction_v1($type = 'payment', $details) {
        insert_query('gocoin_ipn', $details);
    }

?>