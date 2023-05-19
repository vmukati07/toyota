# Module Infosys HazmatShipping

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_HazmatShipping`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are updating shipperhq shipping group and status of a product once the product are getting saved.

	2. Here we are updating shipperhq shipping group based on hazmat flag.

	3. We are updating Product Status based on threshold price

	