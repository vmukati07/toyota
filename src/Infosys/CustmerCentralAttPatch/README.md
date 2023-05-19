# Module Infosys CustmerCentralAttPatch

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CustmerCentralAttPatch`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
    
	1. In this module used for creating customer attribute.
	
	2. Create customer attribute like customer_central_id, phone_number on customer edit form.
	
	3. Create customer attribute customer_central_id on Infosys\CustmerCentralAttPatch\Setup\Patch\Data\AddCustomerAttribute.php
	
	4. Create customer attribute phone_no on Infosys\CustmerCentralAttPatch\Setup\Patch\Data\AddPhoneNumberAttribute.php
	
	5. Update customer_central_id on Infosys\CustmerCentralAttPatch\Setup\Patch\Data\AddPhoneNumberAttribute.php


