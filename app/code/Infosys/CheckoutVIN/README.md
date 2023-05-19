# Module Infosys CheckoutVIN

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CheckoutVIN`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities

	1. This module is used for to add the vehicle indentity number when we place any order.

	2. We have added one column vin_details on sales_order and quote database table to store the vin detail on order place.

	3. For inserting the vin detail on order data we have created a observer (Infosys\CheckoutVIN\Observer\SaveVinDetails.php)

	4. We have showing VIN detail on order detail page on admin.
	
	5. Created resolver file to update the VIN detail in quote on checkout for graphql
	   (Infosys\CheckoutVIN\Model\Resolver\Checkout\CheckoutVIN.php)
	   
	6. Created resolver file to update the vin details in the customer orders query on graphql
	   (Infosys\CheckoutVIN\Model\Resolver\Order\OrderVinDetails.php)
	   
	7. Created resolver file to update the vin details in the cart query response on graphql
	   (Infosys\CheckoutVIN\Model\Resolver\Quote\QuoteVinDetails.php)

## Issues Fixed

	1. EP-5299: AC Admin - Use CSS to condense order page display
		- Added new class 'orderdata' in sections of sales order view page for remove whitespace using CSS.(Infosys\CheckoutVIN\view\adminhtml\templates\order\view\view.phtml)