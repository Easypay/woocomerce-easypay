# Easypay WooCommerce plugins 
These plugins serve as middleware between your shop and easypay’s online payment services (APIs). You may have one or more plugins installed, depending on your contract with easypay.
You can even test easypay’s online payment services with these plugins before going into production.

# Available payment method plugins
- Credit Card (single payment only)
- Multibanco
- MBWay
- Universo Flex (single payment only)
- Santander Consumer Finance (single payment only)

# How it works
## In general 
Your clients checkout their order and pay you using Credit Card / Multibanco / MBWay /Santander Consumer Finance / Universo Flex through easypay’s payment plugin. 

Once the payment process is completed easypay’s online services notify the plugin and you can verify it on easypay’s Backoffice or on your own system.

## In particular
### WooCommerce Easypay Credit Card
This plugin allows for (single payment) credit card order payment at your shop, enabling a costumer to jump into our gateway for safe credit card authorization and later (manual ou automatic) values capture. 

When your customer checks out he / she will be taken to easypay’s credit card gateway to enter the credit card details. After the transaction is authorised by easypay’s services he / she is redirected back to your online shop. 

If you have automatic capture configured the plugin tries to capture the funds as soon as possible. 

Once the payment process is completed easypay’s online services notify the plugin.
 
### WooCommerce Easypay Multibanco
This plugin allows for Multibanco order payment at your shop using easypay as a payment identifier provider. 

When your customer checks out he / she will receive an email with the necessary payment identifier to pay on an Multibanco machine or on they’re online banking. 

Once the payment process is completed easypay’s online services notify the plugin. 

### WooCommerce Easypay MBWay
This plugin allows for MBWay order payment at your shop using easypay as a payment provider and the MBWay app installed on the customer’s cellphone. 

When your customer checks out he / she will receive an notification on they’re MBWay app that you wish to bill they’re account with the order value. 

The plugin waits for 5 minutes for the customer’s OK or NOT OK. When the customer allows the transaction the plugin tries to get the funds from the customer if automatic capture is configured, if not you may do it on easypay’s Backoffice. 

If the 5 minutes are over and the customer does nothing the order and authorisation are automatically canceled. 

### WooCommerce Easypay Santander Consumer Finance
This plugin allows for (single payment) santander consumer order payment at your shop, enabling a costumer to jump into santander consumer finance gateway for safe finance process.

When your customer checks out he / she will be taken to santander consumer finance gateway to enter the card details.

Once the payment process is completed easypay’s online services notify the plugin.

___Note:___ _Santander consumer finance services only allow payment amounts between_ ****200€**** and ****6000€****.
_So in your shop, the plugin will hide this payment method automatically for order amounts that is out of this range._

### WooCommerce Easypay Universo Flex
This plugin allows for (single payment) Universo Flex order payment at your shop, enabling a costumer to jump into our gateway for safe authorization and later (manual ou automatic) values capture.

When your customer checks out he / she will be taken to Universo Flex gateway to enter the card details and choose how the customer wish to pay. After the transaction is authorised by Universo Flex services he / she is redirected back to your online shop.

If you have automatic capture configured the plugin tries to capture the funds as soon as possible.

Once the payment process is completed easypay’s online services notify the plugin.


## Before installing the plugin...
You **must** uninstall previous versions of the plugin. We strongly recommend against having older versions of the plugin installed simultaneously.

You **must** have an easypay payments account - [sandbox](https://backoffice.test.easypay.pt/login) or [production](https://backoffice.easypay.pt/login) - with the desired payment method activated by your easypay business manager.

After you have your account activated you must go to _WebServices_ > _Configuration API 2.0_ > _[Your payments account]_ > _Keys_ > _New_ (button), name your key and save it. You will be redirected back and the table will show the Account ID and API Key. You will need this in your plugin. Check the images below.  

> Table that show available Account ID and API Key pairs
![Table that show available Account ID and API Key pairs](doc-images/webservices-config-api-2-account_id-api_key.png "Table that show available Account ID and API Key pairs")
> Name the new Account ID and API Key pair that the system will create
![Name the new Account ID and API Key pair that the system will create](doc-images/webservices-config-api-2-new-account_id-api_key.png "Name the new Account ID and API Key pair that the system will create")
> Your new Account ID and API Key pair to configure/use in the plugin
![Your new Account ID and API Key pair to configure/use in the plugin](doc-images/webservices-config-api-2-show-account_id-api_key.png "Your new Account ID and API Key pair to configure/use in the plugin")

## Plugin installation
- Download the plugin installation zip file available at [easypay's downloads page](https://easypay.pt/en/woocommerce)
- Install the zipped folder with the Wordpress buit-in plugin installation tool **OR** unzip the plugin and copy as a folder to the plugins dir, making sure the web server can read the plugin files directory (555) and files (444)
- Activate the plugin in the plugins list. The plugin is named after the payment method it provides, like this: 
  - Easypay CC
  - Easypay MB
  - Easypay MBWay
  - Universo Flex (single payment only)
  - Santander Consumer Finance (single payment only)

> easypay WooCommerce's payments plugin list
![easypay WooCommerce's payments plugin list](doc-images/woocommerce-payments-plugin-list.png "easypay WooCommerce's payments plugin list")

__Note__: It is required to have WooCommerce previously installed.

## Plugin configuration
All plugins require an **Account ID** and **API Key**. You did this before in **Before installing the plugin...**

> Place your new Account ID and API Key pair in the plugin
![Place your new Account ID and API Key pair in the plugin](doc-images/woocommerce-easypay-plugin-account_id-api_key.png "Place your new Account ID and API Key pair in the plugin")

### Easypay Sandbox
Check this box if you to use easypay’s sandbox (test mode). 

__Note__: If using sandbox mode the Account ID and API Key must be generated in easypay’s [sandbox backoffice](https://backoffice.test.easypay.pt). Production Account ID and API key pair will not work in debug mode and vice-versa.

__Note__: No real money is used when Debug is enabled. Use this for testing purposes only.

### Debug
Check this box if you to use log actions into a log file in your system. 

Depending on your WooCommerce configuration you may find these files in the plugins directory - /uploads/wc-logs/ - there is a file for errors and another for actions logged by the plugin. These log files rotate so expected it to be suffixed with a date and alpha-numeric hash.

## The following configurations are plugin specific:
### WooCommerce Easypay Credit Card
#### Auto Capture
Check this box if you want the funds to be captured automatically after payment authorisation.

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations. 

> Notification URL to copy&paste into backoffice
![Notification URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-visa_fwd-example.png "Notification URL to copy&paste into backoffice")

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _Configuration 2.0_ > _[Your payments account]_ > _Notifications_ > _Generic - URL_

> Your payments account to be configured in backoffice
![Your payments account to be configured in backoffice](doc-images/webservices-config-api-2-account_id-notifications.png "Your payments account to be configured in backoffice")

> Paste notification URL here
![Paste notification URL here](doc-images/webservices-config-api-2-account_id-notifications_url.png "Paste notification URL here")

#### VISA-FWD URL
This is the URL your customer is redirected to after leaving the gateway, either with payment authorised or not. 

> VISA-FWD URL to copy&paste into backoffice
![VISA-FWD URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-visa_fwd-example.png "VISA-FWD URL to copy&paste into backoffice")

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _URL Configuration_ > _[Your payments account]_ > _VISA:Forward_

> Paste VISA-FWD URL here
![Paste VISA-FWD URL here](doc-images/webservices-config-api-2-account_id-visa_fwd_url.png "Paste VISA-FWD URL here")

### WooCommerce Easypay Multibanco
#### Expiration in Days 
Set how many days you want the payment to be payable. After that that date your customer won’t be available. 

The order is not automatically cancelled when this time is over, that is up to you to decide.

#### Enable Expiration for MB References 
Check this box to enforce payment expiration. Your customer won’t be able to pay after the set number of days 

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations.
If you have more that one payment method, you only need to configure this once. You should use MB by default. If you're not using MB, use MBWay instead.

> Notification URL to copy&paste into backoffice
![Notification URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-mb-notification_url-example.png "Notification URL to copy&paste into backoffice") 

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _Configuration 2.0_ > _[Your payments account]_ > _Notifications_ > _Generic - URL_

> Your payments account to be configured in backoffice
![Your payments account to be configured in backoffice](doc-images/webservices-config-api-2-account_id-notifications.png "Your payments account to be configured in backoffice")

> Paste notification URL here
![Paste notification URL here](doc-images/webservices-config-api-2-account_id-notifications_url.png "Paste notification URL here")

### WooCommerce Easypay MBWay
#### Auto capture
Check this box if you want the funds to be captured automatically after payment authorisation.

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations.

> Notification URL to copy&paste into backoffice
![Notification URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-example.png "Notification URL to copy&paste into backoffice") 

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _Configuration 2.0_ > _[Your payments account]_ > _Notifications_ > _Generic - URL_

> Your payments account to be configured in backoffice
![Your payments account to be configured in backoffice](doc-images/webservices-config-api-2-account_id-notifications.png "Your payments account to be configured in backoffice")

> Paste notification URL here
![Paste notification URL here](doc-images/webservices-config-api-2-account_id-notifications_url.png "Paste notification URL here")


### WooCommerce Easypay Universo Flex
#### Auto Capture
Check this box if you want the funds to be captured automatically after payment authorisation.

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations.

> Notification URL to copy&paste into backoffice
![Notification URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-visa_fwd-example.png "Notification URL to copy&paste into backoffice")

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _Configuration 2.0_ > _[Your payments account]_ > _Notifications_ > _Generic - URL_

> Your payments account to be configured in backoffice
![Your payments account to be configured in backoffice](doc-images/webservices-config-api-2-account_id-notifications.png "Your payments account to be configured in backoffice")

> Paste notification URL here
![Paste notification URL here](doc-images/webservices-config-api-2-account_id-notifications_url.png "Paste notification URL here")

#### VISA-FWD URL
This is the URL your customer is redirected to after leaving the gateway, either with payment authorised or not.

> VISA-FWD URL to copy&paste into backoffice
![VISA-FWD URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-visa_fwd-example.png "VISA-FWD URL to copy&paste into backoffice")

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _URL Configuration_ > _[Your payments account]_ > _VISA:Forward_

> Paste VISA-FWD URL here
![Paste VISA-FWD URL here](doc-images/webservices-config-api-2-account_id-visa_fwd_url.png "Paste VISA-FWD URL here")

> Notification URL to copy&paste into backoffice
![Notification URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-example.png "Notification URL to copy&paste into backoffice")

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _Configuration 2.0_ > _[Your payments account]_ > _Notifications_ > _Generic - URL_

> Your payments account to be configured in backoffice
![Your payments account to be configured in backoffice](doc-images/webservices-config-api-2-account_id-notifications.png "Your payments account to be configured in backoffice")

> Paste notification URL here
![Paste notification URL here](doc-images/webservices-config-api-2-account_id-notifications_url.png "Paste notification URL here")

### WooCommerce Easypay Santander Consumer Finance

#### Notification URL
This is the URL in your system that easypay will use to communicate with the plugin, to communicate payment status or other operations.

> Notification URL to copy&paste into backoffice
![Notification URL to copy&paste into backoffice](doc-images/woocommerce-easypay-plugin-notification_url-visa_fwd-example.png "Notification URL to copy&paste into backoffice")

Copy&paste this url into easypay's Backoffice web services configuration, _WebServices_ > _Configuration 2.0_ > _[Your payments account]_ > _Notifications_ > _Generic - URL_

> Your payments account to be configured in backoffice
![Your payments account to be configured in backoffice](doc-images/webservices-config-api-2-account_id-notifications.png "Your payments account to be configured in backoffice")

> Paste notification URL here
![Paste notification URL here](doc-images/webservices-config-api-2-account_id-notifications_url.png "Paste notification URL here")


## Final Notes
We strongly recommend against having older versions of the plugin installed at the same time. We recommend you uninstall older version and only use this new one.

If you'd like to understand how the plugin uses easypay's payment APIs you can read our online docs available at https://api.prod.easypay.pt/docs

