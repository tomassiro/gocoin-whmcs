## Changelog

#### v1.1.0
1) Check and Warning message for minimum php 5.3.0 version requirement added and if PHP version is less than 5.3.0 the payment opion is disabled on frontend.<br>
2) Fixed the scope operators scope=user_read+invoice_read_write and remove merchant_read (file 	catalog/includes/modules/payment/gocoinpay.php)<br>
3) Fixed validations and display of messages & log error (file	/includes/modules/payment/gocoinpay.php)<br>

#### v1.2.0
1) Change in GoCoin Configration Setting in admin.<br>
2) Change in User Authontication while invoice creation.<br> 
3) Change in Signature and hash creation validation.<br>
4) Change in payment  validation and log the event.<br>