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
 
### WooCommerce Easypay Gateway Multibanco
This plugin allows for Multibanco order payment at your shop using easypay as a payment identifier provider. 

When your customer checks out he / she will receive an email with the necessary payment identifier to pay on an Multibanco machine or on they’re online banking. 

Once the payment process is completed easypay’s online services notify the plugin. 

## Before installing the plugin…
You must have an easypay payments account - _sandbox_ or _production_ - with the desired payment method activated by your easypay business manager.

After you have your account activated you must go to WebServices > Configuration API 2.0 > Your_Payments_Account > Keys > New , name your key and save it. You will be redirected back and the table will show the Account ID and API Key. You will need this in your plugin.  

(doc-images/webservices-config-api-2-account_id-api_key.png "Account ID and API Key")

## Plugin installation
- Download the plugin installation zip file available at https://docs.easypay.pt/download
- Install the zipped folder with the Wordpress buit-in plugin installation tool **OR** unzip the plugin and copy as a folder to the plugins dir, making sure the web server can read the plugin files directory (555) and files (444)
- Activate the plugin in the plugins list. The plugin is named _WooCommerce Easypay Gateway Multibanco_

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
### WooCommerce Easypay Gateway Multibanco
#### Expiration in Days 
Set how many days you want the payment to be payable. After that that date your customer won’t be available. 

The order is not automatically cancelled when this time is over, that is up to you to decide.

#### Enable Expiration for MB References 
Check this box to enforce payment expiration. Your customer won’t be able to pay after the set number of days 

## Final Notes
If you'd like to understand how the plugin uses easypay's payment APIs you can read our online docs available at https://api.prod.easypay.pt/docs