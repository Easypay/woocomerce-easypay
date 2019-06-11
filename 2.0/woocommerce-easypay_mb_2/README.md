## woocomerce easypay 2.0 payment gateway modules

### Installation:
- Install from zip file, you can use the wordpress plugin
installation tool or unzip directly to the plugins folder

### Configuration:

Configure with your **account id** and key generated on easypay backoffice:
Web Services -> Configuration API 2.0
    
For sandbox mode checked the keys must be generated at:
- https://backoffice.test.easypay.pt

For production mode:
-  https://backoffice.easypay.pt
        
You can enable the expiration date for your payment identifiers
Setting this mode will enable to set up the number of days a payment identifier 
Can be payed by your costumer
    
You should copy your **Notification URL** to:
    Web Services -> Configuration API 2.0 -> notifications -> Generic - URL

### Methods:

#### Multibanco:

