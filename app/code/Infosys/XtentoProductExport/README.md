# Module Infosys XtentoProductExport

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_XtentoProductExport`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Purpose
	This module developed to increase performance of xtento export feature and to manage google feed profiles dynamically for each store.

## Configuration

	- Store Configuration: Setup following store configuration under the Stores > Configuration > TOYOTA > Products Export > Export 	Configuration
	- Logs: It's used to enable/disable logs
	- Apply Products Export Changes: If this config set to Yes, custom changes will be applied on xtento product export.

## Main Functionalities
	1. Overridden product export entity to improve performance.
	2. Plugin to add following custom attribute filters for product export profiles:
		a. include_in_feed
		b. product_feed_image
	3. Event to add custom variable for dealer_code in exported file name.
	4. Patch to create and update google feed profiles dynamically for each store.
	5. Plugin to add store name and link dynamically in output format for exported file.
	6. Commands to create and manage google feeds for all stores:
		a. Command to generate google feed profiles for missing stores based on existing profile -
			Input: profile id
			php bin/magento export:profile:create --profile_id=72
		b. Command to update existing google feeds based on latest conditions configured in one store -
			Input: profile id
			php bin/magento export:profile:update --profile_id=72
		c. Command to show missing google feeds for stores - 
			php bin/magento export:profile:missing

## Manual testing
	- Go to Catalog > Product Export > Manual Export in backend and run export for selected profile.

## Issue Fixed
	- Customer Website permission issue fixed for print order from vendor.