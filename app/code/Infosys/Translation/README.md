# Module Infosys Translation

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_Translation`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we have enabled the translation for text message on GraphQl area.
	2. Inside \Infosys\Translation\i18n\en_US.csv, we have added text messages that's need to be translated.

## Issues Fixed
	1. EP-5204: Change the message "Configuration changes will be applied by consumer soon." to "Your changes have been saved and will take a few minutes to appear." on save configuration setting.