# Module Infosys StorePickUp

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_StorePickUp`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Configuration->Sales->Delivery Methods->Store Pickup.
	- Enabled: It's used to enable/disable store pickup method.
	- Title: It's used to method title.
	- Method Name: It's used to method name.
	- Price: It's used to define method price.
	- Calculate Handling Fee: It's used to define that handling fee calculate as a fixed amount or percent.
	- Handling Fee: It's used to define handling Fee for store pickup method.
	- Displayed Error Message: It's used to define which error massage we need to display for store pickup method.
	- Ship to Applicable Countries: It's used to select applicable countries for store pickup method.
	- Ship to Specific Countries: It's used to select perticular Specific countries for store pickup method.
	- Sort Order: It's used to define sort order for method.

## Main Functionalities
	1. In this module, we are creating a store pickup shipping method.

	2. Based on configuration we have set method title and calculate the price with handling Fee, also apply on selected Countries.

	3. Place an order from Frontend with "Store Pick up" Shipping Method.

	4. Go to the Admin SALES -> Orders Grid and apply shipping method filter from the filters-> Shipping Method option and select option "In Store Pickup". 

	5. Select any order and click on the "Notify Order is Ready for Pickup" button, it will change the order status from pending to processing, Create the Shipment and send “Order is Ready for Pickup” email to the customer, after notifying the customer, dealer can create the Invoice to complete the order and customer will get the invoice notification email.

	6. As well as we removed adminshipping default store Pick up method from Sales Order Grid, Filter Shipping Method option dropdown.
    7. AEM sending GraphQL request to Magento where we are setting Pickup Location(pickup_address) to Shipping Method if carrrier_code is "dealerstore".
