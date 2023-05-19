# Module Infosys DealerSavings

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_DealerSavings`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. On this module we are created some resolver file to get dealer discount price on cart and order on graphql.

	2. We implement the observer for getting dealer discount value on graphql with cart (Infosys\DealerSavings\Model\Resolver\CartDealerSavings.php)

	3. We implement the observer for getting dealer discount value on graphql with order (Infosys\DealerSavings\Model\Resolver\OrderDealerSavings.php)


