# Module Infosys DealerShippingCost

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_DealerShippingCost`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores -> Configuration -> ShipStation -> General Settings
	- Integration Settings used for manage API url, key and screte key
	- API connect Timeout
	- Logs: It's used to enable/disable logs

## Main Functionalities
	1. In this module used for handling the shipping cost through the different sources.

	2. We have added fields action and shipment_id on database table df_sales_order_freight_recovery.

	3. Added fields shipment_action on table sales_shipment

	4. Create ovserver file for managing the shipping cost from different sources on sales_order_shipment_track_save_after.

	5. We have create a model to update manual dealer shipping cost in df sales order table (Infosys\DealerShippingCost\Model\ManualShippingCost.php)

	6. We create a model to get shipments list (Infosys\DealerShippingCost\Model\Shipstation.php)

	7. Method to update shipstation shipping cost in df sales order table (Infosys\DealerShippingCost\Model\ShipstationShippingCost.php)

## Issues Fixed
	1. EP-5274: AC: Fix issue saving shipping fee when there is no tracking number
		- Included the event 'sales_order_shipment_save_after' to get the 'Freight Recovery' fee and service fee even if the tracking number is not provided in the Direct Fulfillment - Shipment import.		


