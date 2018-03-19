## woocomerce-easypay
### Credit Card Payments

This Woocommerce plugin allows for credit card single payments, enabling a costumer to jump into our gateway for safe credit card authorization and later manual ou automatic values capture. It is required to have a production or sandbox **easypay account** in order to operate with this plugin.

### To install the module:

* Copy as a folder to the plugins dir, checking the proper file permissions

or

* Install the zipped folder with the Wordpress buit-in plugin installation tool

*Note: It is required to have Woocommerce installed before activation.*

* Activate the **WooCommerce Easypay Gateway Credit Card** in the plugins list

### To configure the module:

* Click into Woocommerce -> Settings -> Checkout -> **Easypay Credit Card** - And configure all **easypay account** data
* Copy the URLs at the bottom of the configuration page into your easypay backoffice, webservices section. 

### Multibanco Payments

This Woocommerce plugins allows for Multibanco order payment at your shop using easypay as a payment identifier provider. It is required to have a production or sandbox **easypay account** in order to operate with this plugin.


### To install the module:

* Copy as a folder to the plugins dir, checking the proper file permissions

or

* Install the zipped folder with the Wordpress buit-in plugin installation tool

*Note: It is required to have Woocommerce installed before activation.*

* Activate the **WooCommerce Easypay Gateway Multi Banco** in the plugins list

### To configure the module:

* Click into Woocommerce -> Settings -> Checkout -> **Easypay MB** - And configure all **easypay account** data
* Copy the URL at the bottom of the configuration page into your easypay backoffice, webservices section. 

### Split Payments - *Marketplaces*

This Woocommerce plugin allows for *marketplaces* in a basic way by associating product categories, as *brands*, with an **easypay sub-account** filiated to a configured **easypay master account**

### To install the module:

* Copy as a folder to the plugins dir, checking the proper file permissions

or

* Install the zipped folder with the Wordpress buit-in plugin installation tool

*Note: It is required to have Woocommerce installed before activation.*

* Activate the **WooCommerce Easypay Gateway Split Payments** in the plugins list

### To configure the module:

* Click into Woocommerce -> Settings -> Checkout -> **Easypay Split** - And configure all easypay **master account** data
* Copy the URLs at the bottom of the configuration page into your easypay backoffice, webservices section.

* Click into Products -> Categories:
	1. Create a category for each sub-account.
		* Example: *Shoes Brand Stall*, *Trousers Brand Stall*, *Fruit Market Stall* , *Meat Market Stall*
	2. Input the easypay client data for each created category. Each category will an afiliated easypay account.

* Create your products and add them to their corresponding category. A product can only have **one** category
