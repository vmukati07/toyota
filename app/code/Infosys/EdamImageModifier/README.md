# Module Infosys EdamImageModifier

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_EdamImageModifier`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are building product image url, we are taking product image path for making the url

	2. We are checking image type, size and path while uploading image.

	3. Observer ChangeGalleryTemplate will help into buliding the gallery page.


