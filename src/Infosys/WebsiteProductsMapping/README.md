# Module Infosys WebsiteProductsMapping

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_WebsiteProductsMapping`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA->Website Products Mapping->Assign all products to store
	- Logs: enable/disable.

## Main Functionalities
	1. In this module, we have created store configuration under the "TOYOTA->Website Products Mapping->Assign all products to store" to set All Products to Store while Importing Products.
	2. We have added custom code to add All Products to Store while Importing Products.
	3. We are using RabbitMQ to accomplish this functionality.
	4. We are using an event observer Infosys\WebsiteProductsMapping\Observer\AssignProductsToWebsite to assign Products to Store.
