# Module Infosys UpdateProductCount

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_UpdateProductCount`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA -> Product Count Configuration -> Update Product Count
	- Here 'Update Product Count' Field value should be less than or equal to 500000.

## Main Functionalities
	1. In this module, we are increasing the Product Count after applying filter on Frontend Catalog Category Pages for Elastic Search Engine Builder.
	2. For that we have created a Store configuration, STORES -> Configuration -> TOYOTA -> Product Count Configuration -> Update Product Count.
	3. In configuration we can set total Product Count value using 'Update Product Count' field.
	4. We have overrided the Magento Elastic Search Index Builder with max_resul_window of '500000'.
	5. As well as we are setting Magento Search PageSizeProvider to get custom Product count from our Configuration value.
	6. Finally we are making curl request to connect with Elastic Search Engine with our custom maximum product count value, server host and port.
	7. For more understanding, Please go through complete module (Infosys/UpdateProductCount) codebase.