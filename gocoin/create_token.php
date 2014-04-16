<?php
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");

$moduleName = "gocoin";
$GATEWAY = getGatewayVariables($moduleName);

$file = __DIR__ . '/gocoinlib/src/GoCoin.php';
if (file_exists($file)) {
    include_once($file);

    function shoGocoinToken($GATEWAY) {
        $code = isset($_REQUEST['code']) && !empty($_REQUEST['code']) ? $_REQUEST['code'] : '';
        $client_id = $GATEWAY['module_payment_gocoin_client_id'];
        $client_secret = $GATEWAY['module_payment_gocoin_sequrity_key'];

        try {
            $token = GoCoin::requestAccessToken($client_id, $client_secret, $code, null);
            echo "<b>Copy this Access Token into your GoCoin Module: </b><br>" . $token;
        } catch (Exception $e) {
            echo "Problem in getting Token: " . $e->getMessage();
        }
        die();
    }

    shoGocoinToken($GATEWAY);
}
?>