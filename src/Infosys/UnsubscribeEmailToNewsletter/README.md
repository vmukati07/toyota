# Module Infosys UnsubscribeEmailToNewsletter

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_UnsubscribeEmailToNewsletter`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we have created a graphQl call to unsubscribe Newsletter where GraphQl request is calling from AEM to Magento to unsubscribe the Newsletter for a Customer.
	2. The Functionality for GraphQl Call available in Infosys\\UnsubscribeEmailToNewsletter\\Model\\Resolver\\UnSubscribeEmailToNewsletter .