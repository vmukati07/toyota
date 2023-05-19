# Module Infosys OrderView

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_OrderView`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Toyota->Shipment Tracking Links
	- DHL: Configure DHL
	- FeDex: Configure FeDex
	- UPS: Configure UPS
	- USPS: Configure USPS

## Main Functionalities
	1. In this module, we have created order tracking/view result pages.

	2. We have created plugin to remove shipstation carrier in the result

	3. In view page we are building view page with order related information
	
## Issues Fixed

	1. EP-5299: AC Admin - Use CSS to condense order page display
		- Added new class 'orderdata' in sections of sales order view page for remove whitespace using CSS.
		(Infosys\OrderView\view\adminhtml\templates\order\view\tab\info.phtml,Infosys\OrderView\view\adminhtml\templates\order\view\info.phtml)

		- Added CSS for remove whitespace from sales order view page sections.
		(Infosys\OrderView\view\adminhtml\web\css\remove_orderview_whitespace.css)