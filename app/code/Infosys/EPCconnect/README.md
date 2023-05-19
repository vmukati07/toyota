# Module Infosys EPCconnect

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_EPCconnect`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: 
		- Setup following store configuration under SFTP credentials
			- Host: Enter host url
			- SFTP user: Enter user Id
			- SFTP ssh: Enter ssh detail
		- Enable URL Rewrites During Import
			- Enable by yes no button

## Main Functionalities
	1. This module provides customizations while importing products by overriding classes of "CatalogImportExport" and "CatalogUrlRewrite" module.
	
	2. File validator will validate the uploaded files for required attributes
	
	3. Assigning vehicles to products as per the "model_year_code" attribute in the "_saveVehicleProductMapping" function.

	4. Calculating the vehicle factes during the vehicle product mapping by injecting dependency of the "Vehicle
	Search\Model\FitmentCalculations" file.
	
	5. Notifying dealers by email (as per store->configuration) if any new category found during the import.
	
## Issues fixed
	1. EP-5108: Fix for model_year_code allowing null values or without model_year_code column during product import.