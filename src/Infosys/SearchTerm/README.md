# Module Infosys SearchTerm

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_SearchTerm`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are creating Resolver class to get search terms based on query.

	2. With the help of graphQL, it will perform term based search

	3. We have applied the patch into databsed for search term. 