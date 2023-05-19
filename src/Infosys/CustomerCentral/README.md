# Module Infosys CustomerCentral

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CustomerCentral`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores > Configuration > Toyota > Customer central
	- Have option for saving Token API Details, Save Customer API Details, Parts Online Purchase API Details, Order Sync Cron Settings.
	- Have the option for enable and disable the log.
	- We have the option where we have set the max retry count Order Sync Cron Settings

## Main Functionalities

	1. This module provide the ability to sync the customer with customer central.
	
	2. Have a separate database table for customer central (customer_central_order_queue) with field
		1. queue_id
		2. order_id
		3. retry_count
		4. api_status
		5. created_at
		6. updated_at
		7. messages
		
	3. Have ACL permission to access this functionality where admin can select the permission for dealer.
	
	4. When any customer will be added with store then customer sync automatically with the customer central
	
	5. If any customer will be updated from admin or frontend then it will be sync with customer central
	
	6. Any order place on store it will be sync with customer central (Infosys\CustomerCentral\Observer\OrderPlaceAfter.php).
	
	7. We also have the functionality to sync order manually with customer central.
	
	8. Customer central request code is available in Infosys\CustomerCentral\Model\CustomerCentral.php.
	
	9. Any customer will be login it will sync with the customer ceantral (Infosys\CustomerCentral\Observer\SyncCCLogin.php)
	
	10. Any customer will be register it will sync with the customer ceantral (Infosys\CustomerCentral\Observer\SyncCCRegister.php)
	
	11. We have setup cron for sync the pending order with customer central (Infosys\CustomerCentral\Cron\CustomerCentralOrderQueue.php)
	
	12. Here we can retry to sync pending order with customer central (Infosys\CustomerCentral\Cron\CustomerCentralRetryOrderQueue.php)
	
	13. Have a separate log file to debuging it (var/log/toyota_customercentral.log)

## Issues Fixed

	1. EP-5259: Clicking on Customer Central button getting an error "There has been an error processing your request"
		- Removed the unused array index from Infosys\CustomerCentral\Model\CustomerCentral.php. This resolved the page break and will display the proper error response to the user.
		- Added the API request as Info log, before the CURL call.
		- Included try/catch blocks to handle the exceptions.
		- Printed the full stack trace of exception instead of just the message.
