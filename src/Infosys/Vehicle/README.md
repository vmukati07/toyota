# Module Infosys Vehicle

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_Vehicle`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Toyota->Dealer Brand
	- Brand configuration: Choose the required brands from list
	- Setup following store configuration under the Stores->Toyota->Vehicle
	- Vehicle configuration: Scheduled Import Failed Email (keep the email for failed email data)
	- Vehicle Image Placeholder: Upload the image
	- SFTP credentials: Keep the detail for Host, SFTP USER, SFTP SSH
	- Vehicle Aggregations: Select yes/No for Year,Model, Trim Level, Driveline, Body Style, Engine Type, Transmission
	- Cron Settings: Enable SFTP Files Sync, Enable Scheduled Tasks
	- Logs: It's used to enable/disable logs
	- Vehicle Data Find/Replace: Enable/Disable Replace
	- Import Settings: 
		- Allowed Errors Count: Keep the error count value
		- Enable/Disable Vehicle Fitment During Import: Yes/No
		- Enable/Disable Vehicle Fitment Calc update Cron: Yes/No
		- Enable/Disable Vehicle mapping insert: Yes/No



## Main Functionalities
	1. In this module, we are creating a report to calculate the rank of dealer as per the selected filter options and displaying the result in the backend grid format along with export feature.

	2. We have created the table, toyota_dealer_sales_statistics where we're keeping all data of sales on the basis of date and store Id.

	3. In order to calculate and save the sales statistics, we used the Store Procedure that's calculating all the sales statistics and save the result into toyota_dealer_sales_statistics table.

	4. We have a cron job that will run daily to pull the list of all store_ids and insert it into the toyota_dealer_sales_statistics_queue table.

	5. We have a cron job that call the Store Procedure at daily basis to calculate yesterday's sales record. 

	6. We are also upgrading the data in the toyota_dealer_sales_statistics table when any order is updated from the backend.


		
