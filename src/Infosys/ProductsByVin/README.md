# Module Infosys ProductsByVin

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_ProductsByVin`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA->Vehicle->Search Customizations
	- Here we can enable/disable "Enable Search by Vin" setting and controlling search suggestion with "Enable Search Suggestions" field from Configurations.

## Main Functionalities
	1. In this module, We are using this module to control search result on Product Listing Page on frontend.
	2. We have overrided the search result functionality if it Vin Number and with we are checking setting if "Enable Search by Vin" is Yes as well as filtering search keywords as like: aplphabets, special chars and numeric numbers.
	3. We are also checking 'model_year_code' it's related setting from Configuration TOYOTA->Vehicle->Vehicle Aggregations->model_year_code Override before search result and remove other vehicle filters then add them back in after we get our results then this fixes an issue where products don't show up on PLP pages with selected vehicle after vehicle data updates.
	4. Also we are checking brand filter with Search Suggestion setting.
	5. Based on "Enable Search by Vin" and "model_year_code Override" setting we are sending GraphQL response for search result to AEM.