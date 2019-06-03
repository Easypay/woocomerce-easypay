=== WooCommerce Easypay Payment Gateway ===
Tags: payment gateway, gateway, woocommerce, woocommerce gateway, easypay, payment
Requires at least: PHP 5.5.3 and MySQL 5.5.33 and WordPress 3.9.1 and WooCommerce 2.1.12+
Tested up to: 3.8.1
Stable tag: 1.00

This is a WooCommerce payment gateway, that allows your clients to use Easypay gateway to pay with Multibanco or Credit Card.

== Description ==

With this plugin you can enable Multibanco and Credit Card payments through easypay.

The plugin adds a new gateway on woocommerce gateways configuration panel to allow shop administrators to Configure the your easypay credentials. 

= Features =
* Multibanco payment through easypay gateway.
* Credit Card payment through easypay gateway.

= Localization =
* English (default) - always included
* Portuguese - always included
* *Your translation? - [Just send it in](tec@easypay.pt)*

Credit where credit is due: This plugin here was developed by André Filipe...

= Feedback =
* We are open for your suggestions and feedback! send it to tec@easypay.pt

== Installation ==

1. Upload the entire `woocommerce-easypay` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Look under the regular WooCommerce settings menu: "WooCommerce > Settings > Tab "Payment Gateways" and adjust the easypay gateway configurations to your needs
4. It's mandatory that you set the currency to "Euros", otherwise easypay payment won't be available on the checkout
5. Good luck with sales :)

**Please note:** You must run WordPress 3.5 or higher and WooCommerce 2.0 or higher in order tun this plugin. Previous Versions were not tested.

== Frequently Asked Questions ==

= Easypay doesn't show on payment methods? =
This may happen because easypay payment gateway only works with Euros (check the currency option on "General" tab of the WooCommerce settings), if thats not the case you may have to check the "Enable/ Disable" option on the easypay gateway configuration, under the "Payment Gateways" tab.

== Changelog ==

= 1.0 =
Just released into the wild.

== Additional Info ==
**Idea Behind / Philosophy:** Don't leave for tomorrow what you can receive today
