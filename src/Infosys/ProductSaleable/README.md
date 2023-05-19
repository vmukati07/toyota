# Module Infosys ProductSaleable

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_ProductSaleable`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Toyota->Disable Product
	- Product Disable Threshold Price: Set the threshold price	
	- Logs: It's used to enable/disable logs
	- Configuration setting for setting out of stock AAP product based on setting Stores->Toyota->Disable AAP Products
	-  Configuration setting for setting disable AAP product based on setting Stores->Toyota->Hide AAP Products

## Main Functionalities
	1. In this module, we are managing thre price for saleable product

	2. We have created helper file to get the product threshold price and product stock status.

	3. tier price set stock under the publisher file

	4. Observer PriceThresholdChange to update product status after price change and to update product status based on threshold price.

	5. Observer ProductSaleable to Save stock status data from a product to the Stock Item. Global manage stock is disable. so need to enable/disable before update the stock status

	6. Observer StockStatusChange to update product stock after stock configuration change as per tier price set
	
	7. We have created the table, threshold_price_queue where we're keeping threshold price.

	8. We have a cron job that will run daily to update product status based on threshold price.

	9. All product will be disable if product tier price set is set to AAP and configuration setting set to Yes otherwise it will work based on threshold price setting.


