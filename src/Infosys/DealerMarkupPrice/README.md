# Module Infosys DealerMarkupPrice

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_DealerMarkupPrice`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, We are calculating Dealer Markup Price after substracting Shipping Details Cost from Shipping Details Price.
	2. Dealer Markup Price = Shipping price - Shipping cost;
	3. We have added new column 'dealer_markup_price' in sales_order table to save Dealer Markup Price with Order details. 

## Fixed Issues
	1. EP-5261 Carrier Type Shows Carrier for In-Store Pickup Item
		- Fixed carrier type issue for in store pickup & flat rate orders in sales order grid. 