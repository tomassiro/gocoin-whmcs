Version 1.0.0 BETA

�2014 GoCoin Holdings Limited and GoCoin International Group of companies hereby grants you permission to utilize a copy of this software and documentation in connection with your use of the GoCoin.com service subject the the published Terms of Use and Privacy Policy published on the site and subject to change from time to time at the discretion of GoCoin.<br><br>

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE DEVELOPERS OR AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.<br><br>

## Using the Official GoCoin WHMCS Plugin
When a shopper chooses the GoCoin payment method and places their order, they will be redirected to gateway.GoCoin.com to pay.  
GoCoin will send a notification to your server which this plugin handles.  Then the customer will be redirected to an order summary page.  

The order status in the admin panel will be "Processing" when the order is placed and payment has been confirmed. 

#### Important Note: 
Version 1.0.0 of this plugin only supports US Dollars as the Base Currency. Please make sure your Currency is set to US Dollars. Support for additional currencies is coming soon. 

This plugin now supports Bitcoin, Litecoin as well as Dogecoin

### 1. Installation
[WHMCS](http://www.whmcs.com/) must be installed before installing this plugin.

a. 	Copy downloaded <b>gocoin.php</b> file to <<WHMCS Home>>/modules/gateways/ folder .
b. 	Copy the downloaded <b>gocoin</b> folder and its contents to <<WHMCS Home>>/modules/gateways/ folder.
c. 	Copy the downloaded <b>callback/gocoin.php</b> file to <<WHMCS Home>>/modules/gateways/callback folder.

### 2. Setting up an application.
1) [Enable the GoCoin Hosted Payment Gateway](http://www.gocoin.com/docs/hosted_gateway)<br>
2) Create an application in the [GoCoin Dashboard](https://dashboard.gocoin.com)

##### Navigate to the Applications menu from the dashboard home<br>
![applications](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/rj1pj_-a.png)

##### Create a new application <br>
![applications home](https://dl.dropboxusercontent.com/spa/pvghiam459l0yh2/s61g2gn8.png)<br>
Make sure your redirect_uri is equal to:

```
https://YOUR_DOMAIN/index.php
```

Set the Application and Callback URL. The Callback URL will be https://YOUR_DOMAIN/index.php<br>
Make sure to use https for a production site - its part of the OAuth standard.

More information on creating GoCoin connected applications can be found [here](http://www.gocoin.com/docs/create_application)

### 3. Configuration

1. In the Admin panel click Setup > Payments > Payment Gateways, You will see Dropdown of Payment modules in Activate Module. Locate GoCoin, and click on Activate. <br><br>

2. On Clicking Activate button GoCoin module settings will be displayed, configure GoCoin by entering following<br>
  a) Enter Clicnt ID, Secret key and Access Token<br>
  b) Obtain a token:<br>
    i) Set Client id and Client key <br>
    ii) Click "Get Access token from GoCoin" button. You will be redirected to dashboard.gocoin.com. Allow permission to access your info then you will be redirected back to this page. The Access Token will have populated the field. <br>
3. SAVE AGAIN. You are now ready to accept payments with GoCoin!
