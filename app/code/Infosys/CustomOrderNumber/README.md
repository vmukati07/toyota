# Module Infosys CustomOrderNumber

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CustomOrderNumber`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`
	
## Configuration

	- Store Configuration: Setup following store configuration under the Stores > Configuration > Toyota > Custom Order Number
	- We have the ability to enable or disable this module on configuration.
	- Also have the ability to change the prifix of order number.
	- Default value for Prifix Of Order Number is T for now.

## Main Functionalities

    1. Allow admins to set custom prefix, suffix, and leading zero length values for the order numbers, invoices, shipments, and credit memos by overriding the Magento_SalesSequence module's default pattern

    2. We have implement the ACL permission for this functionality where admin can choose that which role have the access for this functionality.

    3. Override the Magento\SalesSequence\Model\Manager::GetSequence through the plugin for changing order sequence number irrespecive of store
	   (Infosys\CustomOrderNumber\Plugin\Manager.php::beforeGetSequence)