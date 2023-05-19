# Module Infosys CustomerWebsiteOrders

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CustomerWebsiteOrders`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities

	1. In this module, we are geting the all order data of a customer from all the website.

	2. For filter the order data we are overriding the class (Magento\SalesGraphQl\Model\Resolver\CustomerOrders\Query\OrderFilter) through the plugin
	   (Infosys\CustomerWebsiteOrders\Plugin\WebsiteOrders.php)

	3. Getting the product categories for customer order graphql query on order success page, we have created a resolver file.
	   (Infosys\CustomerWebsiteOrders\Model\Resolver\Order\ItemsCategories.php)

	4. Getting guest success order data on graphql we have created a resolver file (Infosys\CustomerWebsiteOrders\Model\Resolver\SuccessOrders.php)

	5. Resolver to get order items (Infosys\CustomerWebsiteOrders\Model\Resolver\SuccessOrdersItems.php)


