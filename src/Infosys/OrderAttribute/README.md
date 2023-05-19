# Module Infosys OrderAttribute

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_OrderAttribute`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Store Email
	
## Main Functionalities
	1. In this module, we are updating order related attribute once the order is created.

	2. We have created plugin to get the payment related detail and to update the quote data once the order is placed.

	3. Table quote_item and sales_order_item tables are pdated using db_schema to update the data type.

	4. Added email template system variable into di.xml file to get store email directly to Email templates.

## Issue Fixed
	EP-5737: Carrier code is required in order details graphql query
	addes 2 fields in customer order query inside first items array
		carrier_code,
		shipping_description
