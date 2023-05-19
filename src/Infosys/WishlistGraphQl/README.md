# Module Infosys WishlistGraphQl

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_WishlistGraphQl`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, We are setting WishList collection for a Customer based on Store for Multi Site because we have faced this issue for Multi Site WishList collection was not working as expected.
    2. We have also changed the GraphQl call for Customer WishList for Multi Site.
	3. For GraphQl call purpose, please refer Infosys\WishlistGraphQl\Model\Resolver\WishlistItems and Infosys\WishlistGraphQl\Plugin\Model\Resolver\CustomerWishlistsfile.