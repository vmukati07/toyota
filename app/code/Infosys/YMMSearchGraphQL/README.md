# Module Infosys YMMSearchGraphQL

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_YMMSearchGraphQL`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA->EFC Configuration->EFC Configuration
	- EFC Configuration: Here we have configured API Key, Url, Vehicle Image Url and Vehicle Image Brand.
	- Logs: Enable/Disable.
	- API Connection Timeout: Here we are maitaining EFC Connection Timeout	and EFC Request Timeout	for GraphQL Call.

## Main Functionalities
	1. In this module, we have created custom GraphQL module for YMM search feature.
    2. Added Grade attribute as dropdown option for vehicle selection and vehicle name in GraphQL response for YMM search.