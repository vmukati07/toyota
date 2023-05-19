# Module Infosys SignifydFingerprintCart

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_SignifydFingerprintCart`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, Signifyd is handling few events like holding, unholding, canceling orders based on guarantee status, this events are handled in updateorder.

	2. We have added custom logic in updateOrder() method to trigger an automatic email whenever an order is cancelled.

	3. We added comment 'Your Order was canceled because it was declined by fraud protection' in order history whenever an order is cancelled.

	4. We have made signifyd fingerprint in Cart Graphql request using new field 'signifyd-fingerprint`.