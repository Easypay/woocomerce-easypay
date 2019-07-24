# Easypay WooCommerce plugins 
These plugins serve as middleware between your shop and easypay’s online payment services (APIs). You may have one or more plugins installed, depending on your contract with easypay.
You can even test easypay’s online payment services with these plugins before going into production.

# Available payment method plugins
- Credit Card (single payment only)
- Multibanco
- MBWay

# How it works
## In general 
Your clients checkout their order and pay you using Credit Card / Multibanco / MBWay through easypay’s payment plugin. 

Once the payment process is completed easypay’s online services notify the plugin and you can verify it on easypay’s Backoffice or on your own system.

## In particular
### WooCommerce Easypay Gateway Credit Card
This plugin allows for (single payment) credit card order payment at your shop, enabling a costumer to jump into our gateway for safe credit card authorization and later (manual ou automatic) values capture. 

When your customer checks out he / she will be taken to easypay’s credit card gateway to enter the credit card details. After the transaction is authorised by easypay’s services he / she is redirected back to your online shop. 

If you have automatic capture configured the plugin tries to capture the funds as soon as possible. 

Once the payment process is completed easypay’s online services notify the plugin.
 
### WooCommerce Easypay Gateway Multibanco
This plugin allows for Multibanco order payment at your shop using easypay as a payment identifier provider. 

When your customer checks out he / she will receive an email with the necessary payment identifier to pay on an Multibanco machine or on they’re online banking. 

Once the payment process is completed easypay’s online services notify the plugin. 

### WooCommerce Easypay Gateway MBWay
This plugin allows for MBWay order payment at your shop using easypay as a payment provider and the MBWay app installed on the customer’s cellphone. 

When your customer checks out he / she will receive an notification on they’re MBWay app that you wish to charge they’re account with the order value. 

The plugin waits for 5 minutes for the customer’s OK or NOT OK. When the customer allows the transaction the plugin tries to get the funs from the customer if automatic capture is configured, if not you may do it on easypay’s Backoffice. 

If the 5 minutes are over and the customer does nothing the order and authorisation are automatically canceled. 

## Before installing the plugin...
You must uninstall previous versions of the plugin. We strongly recommend against having older versions of the plugin installed simultaneously.

You must have an easypay payments account - _sandbox_ or _production_ - with the desired payment method activated by your easypay business manager.

After you have your account activated you must go to WebServices > Configuration API 2.0 > Your_Payments_Account > Keys > New , name your key and save it. You will be redirected back and the table will show the Account ID and API Key. You will need this in your plugin.  

(doc-images/webservices-config-api-2-account_id-api_key.png "Account ID and API Key")

## Plugin installation
- Download the plugin installation zip file available at https://docs.easypay.pt/download
- Install the zipped folder with the Wordpress buit-in plugin installation tool **OR** unzip the plugin and copy as a folder to the plugins dir, making sure the web server can read the plugin files directory (555) and files (444)
- Activate the plugin in the plugins list. The plugin is named after the payment method it provides, like this: 
  - WooCommerce Easypay Gateway Credit Card
  - WooCommerce Easypay Gateway Multibanco
  - WooCommerce Easypay Gateway MBWay

__Note__: It is required to have Woocommerce previously installed.

## Plugin configuration
All plugins require an Account ID and API Key. You did this before.

(doc-images/woocommerce-plugin-account_id-api_key.png "Plugin Account ID and API Key")

### Easypay Sandbox
Check this box if you to use easypay’s sandbox (test mode). 

__Note__: If using sandbox mode the Account ID and API Key must be generated in easypay’s Sandbox Backoffice (https://backoffice.test.easypay.pt).  Production key and id will not work in sandbox mode and vice-versa.

__Note__: No real money is used when this is enabled! Use this for testing purposes only.

### Debug
Check this box if you to use log actions into a log file in your system. Depending on your woocommerce configuration you may find these files in the plugins directory - /uploads/wc-logs/ - there is a file for errors and another for actions logged by the plugin. These log files rotate so expected it to be suffixed with a date and alpha-numeric hash.

## The following configurations are plugin specific:
### WooCommerce Easypay Gateway Credit Card
#### Expiration in Days 
Set how many days you want the payment to be payable. After that that date your customer won’t be available. 

The order is not automatically cancelled when this time is over, that is up to you to decide.

#### Enable Expiration for CC References 
Check this box to enforce payment expiration. Your customer won’t be able to pay after the set number of days 

#### Auto Capture
Check this box if you want the funds to be captured automatically after payment authorisation.

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations. 

Copy&paste this url into easypay's Backoffice web services configuration, Web Services -> Configuration 2.0 -> Payment_Account_XX -> Notifications -> Generic - URL

#### VISA-FWD URL
This is the URL your customer is redirected to after leaving the gateway, either with payment authorised or not. 

Copy&paste this url into easypay's Backoffice web services configuration, Web Services -> URL Configuration -> Payment_Account_XX -> VISA:Forward

### WooCommerce Easypay Gateway Multibanco
#### Expiration in Days 
Set how many days you want the payment to be payable. After that that date your customer won’t be available. 

The order is not automatically cancelled when this time is over, that is up to you to decide.

#### Enable Expiration for MB References 
Check this box to enforce payment expiration. Your customer won’t be able to pay after the set number of days 

### WooCommerce Easypay Gateway MBWay
#### Expiration in Days 
Set how many days you want the payment to be payable. After that that date your customer won’t be available. 

The order is not automatically cancelled when this time is over, that is up to you to decide.

#### Enable Expiration for MBWay References 
Check this box to enforce payment expiration. Your customer won’t be able to pay after the set number of days 

#### Auto capture
Check this box if you want the funds to be captured automatically after payment authorisation.

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations. 

Copy&paste this url into easypay's Backoffice web services configuration, Web Services -> Configuration 2.0 -> Payment_Account_XX -> Notifications -> Generic - URL

## Final Notes
If you'd like to understand how the plugin uses easypay's payment APIs you can read our online docs available at https://api.prod.easypay.pt/docs