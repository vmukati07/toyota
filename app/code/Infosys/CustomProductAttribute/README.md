# Module Infosys CustomProductAttribute

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CustomProductAttribute`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
    
	1. In this module we are creating product attribute through the data patch
	
	2. Creating product attribute on Infosys\CustomProductAttribute\Setup\Patch\Data\\ProductAttribute.php
	
	3. Product attribute will be created after running upgrade command.


