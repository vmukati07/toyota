# Module Infosys ShipStationOrders

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_ShipStationOrders`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are initializing export process. Here we are performing export according to the given request based on store Id. We are checking export type and imported child bundle item.

	2. There is Restriction in store pickup orders and DF, manual shipped order items to sync in Ship station under Await shipping.

	3. Write the order in xml file. Billing info, order item, order discounts,

	4. Get the Gift information of order or item.

	5. Get the Billing information of order

	6. Get the Shipping information of order.

	7. Set the parent image url if the item image is not set.

	8. Write the order discount details in to the xml response data

	9. We have created OrderPlaceAfter observer to set is store pickup flag value on order.

	10. If DF order item will be REJECTED or CANCELLED, order item will be shipped by manually or through shipstation.
			app\code\Infosys\ShipStationOrders\Model\Action\Export.php

