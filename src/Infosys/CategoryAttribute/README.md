# Module Infosys CategoryAttribute

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CategoryAttribute`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are adding a category attribute for featured category where we can filter the featured category as per requirement.

	2. First we have added a column "featured_category" on database category attribute table with int type (Infosys\CategoryAttribute\Setup\InstallData.php).

	3. Added Featured Category field in category form (Infosys\CategoryAttribute\view\adminhtml\ui_component.xml).

	