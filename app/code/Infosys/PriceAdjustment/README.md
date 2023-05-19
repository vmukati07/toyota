# Module Infosys PriceAdjustment
	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation
	- Enable the module by running `php bin/magento module:enable Infosys_PriceAdjustment`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration
	- Store Configuration: Setup following store configuration under the Stores->Configuration->Toyota->Price Configuration
	- There are some validations we have added for percentage input field
		1. MSRP Minus percent maximum (percentage will be not more that the percentage in this field)
		2. Cost Plus percent minimum (percentage will not be below than this percentage in this filed)
	- Special Price Update Product Count, This configuration will be used when we update product tier price set with product import,
		based on the count we are updating special price for the product.
	- We can enable/disable Cron For Media Set Selector Options and Dealer price Calculation during import.
	- We can enable/disable Tier Price Import During Product Import.
	- We have added configuration that whether we need to use RabbitMQ or cron for tier price configuration.
		- Logs: It's used to enable/disable logs

## Main Functionalities
	1. In this module, Dealer price is a feature where dealers can give special price to the customers based on the price ranges of the product. We are using the below dealer pricing model where dealers can add the discount price.

    2. Dealer Pricing Model, here we can add new dealer price rule ie New Media Set using backend Admin Configuration: Dealer Pricing Model -> Tier Pricing -> Add New Row.

	3. New Media Set can be created based on 
	    1. Website (Dealer Website)
		2. Product Type Selector (Attribute Set Type i.e Accessories and Parts)
		3. Media Set Selector (This field depend upon the product type selector field, and it will filter all the products of the selected attribute set id and brand selected from Stores -> configuration -> Vehicle -> Brand.

    4. The query written in Infosys\PriceAdjustment\Cron\UpdateMediaSetSelector.php

	5. We have added this in cron because of performance issues we faced to get result on the fly.

	6. Tier Price – In tier price there are 2 adjustment types with price range and percentages.
		1. Cost + Percentage 
		2. List - Percentage 

	7. Cost + Percentage means, the percentage given in the field will be added to cost of the product.

	8. List – Percentage means, the percentage given in the field will be reduced from the price of the product.

	9. There are some validations we have added for percentage input field, u can find them in Stores -> Configuration -> Toyota -> Price Configuration
		1. MSRP Minus percent maximum (percentage will be not more that the percentage in this field)
		2. Cost Plus percent minimum (percentage will not be below than this percentage in this filed)
	
	10. Dealers add their percentages and next flow is tier price calculation.
	
	11. Exposes a service Model that allows for reindexing catalogsearch_fulltext by storeid dimension and product ids 

	12. Triggering points for dealer price calculation,
		#1 When dealer add the dealer prices from dealer pricing model grid
		    We are using the below custom tables for dealer price calculation process,
		    media_set
		    tier_price
		    tier_queue

		In this scenario whenever anyone add the dealer price and click on Save and Recalculate Prices button it will come to our controller.
		Infosys\PriceAdjustment\Controller\Adminhtml\Percentage\Save.php

		1.	We are doing some validations to prevent duplication of adding tier prices for websites and product type.
		2.	Next, we are inserting data with 
			a. sku as empty, 
			b. website as selected website
			c. tier_price_set as media set selector 
			d. special_price_update_status as N
			e. tier_price_product_type as Product Type
			f. old_product_price, old_tierprice_id as empty (Because it is creating first time) in tier_queue table.

		3.	Next our Cron will run Infosys\PriceAdjustment\Cron\UpdateSpecialPrice.php. 
		4.	This Cron will pick up the entries from tier_queue table as 

		$tierCollection = $tierQueue->getCollection()
					->addFieldToFilter('special_price_update_status', 'N')
					->addFieldToFilter('sku', '')
					->getFirstItem()->getData();

		5. If it found the data then we are calling this function calculatePricesForTierSet()  and we are loading all products with product type and media set filters and updating dealer price in Special price of the product with getSpecialPrice() and setPricesPerStore() functions.

		#2. When there is a save in the product
		1.	When we import a product then product save event will trigger and we are using that event and calculation dealer price. 
		    Infosys\PriceAdjustment\Observer\ ProductQueue.php
		2.	With this observer we are inserting the values with Sku in tier_queue Table.
		3.	Next our cron which I mentioned above will run and call calculatePricesForProducts() function and call product with sku and   update special price of the product.

		4. There is one more cron we have added to get media set selector options in grid.
		   Infosys\PriceAdjustment\Cron\UpdateMediaSetSelector.php

		5. This cron will run for every 6 hours and update the data in brand_product_type_media_set table. Based on the product type    selected we are filtering the values from this table with store id and showing the values 

		6. Infosys\PriceAdjustment\Model\Config\Source\SetOptions.php , this file will show media set selector options.
		To run this cron manually we have added a link under dealer pricing model as Run Media Set Sync

		# Tier price calculation with RabbitMQ
		1. We have added configuration that whether we need to use RabbitMQ or cron for tier price configuration
		   Store Configuration: Stores->Configuration->Toyota->Price Configuration

		2. To improve performance, we use rabbit MQ for tier price calculation. The tier price logic remains same only the difference is    that we are sending the data required for price calculation to queue and consumer will use the data and update the tier price.
		    - There are two queues in place:
		        1. `tier-price.import` - triggered via import
		            - Messages in this queue are consumed by `TierPriceUpdate`
		        2. `tier-price.save` - triggered via saving of Dealer Pricing Model rows 
		            - Messages in this queue are consumed by `TierPriceSave`, which will, if the `catalogsearch_fulltext` Indexer is set to `save`, trigger a dimensional reindex with the storeid as the dimension, targeting only effected products.  

		3. When we save tier price set and import products, we are sending the data to queue
			Please refer to these files: 
			- Infosys\PriceAdjustment\Observer\ProductQueue.php
			- Infosys\PriceAdjustment\Controller\Adminhtml\Percentage\Save.php

			We are using publisher to publish data. 
			Based on the configuration only we will use RabbitMQ.
			Before publishing message, we need to run these commands in local to active RabbitMQ

			bin/magento queue:consumers:start tier-price.import
			bin/magento queue:consumers:start tier-price.save

			Above command is used only for windows. If we are using the linux means, don’t need to run the command.

			For checking the connection and queue,

			http://localhost:15672/#/connections 

			And it will show one connection running

## Optimization
	EP-5947: Tier Price logic optimization for dealer price update from dealer login
	1. Created new model file dealer DealerPrice.php
	2. This model file contains following methods that will be used on other modules to calculate prices for dealers.
		1. getSpecialPrice(): This will accept the product and array of websites as a input argumant and return the array of udpated prices for each websites 
		2. getDealerDiscountedPrice(): This will be called in the above getSpecialPrice() function for each website and this will calculate the prices for the given website and product.
		3. setPricesPerStore(): This will set/remove the prices for the websites as per given array.

## Improvement
	EP-5933: Improvement Tier price rules price update from dealer login
	1. Added code to insert created at, udpated at, updated by column in the media_set table.
	2. Fixed below two issues
		1. Clicking on save and continue edit in Media set is triggering price calculation
		2. Saving product from admin was invoking observer app\code\Infosys\PriceAdjustment\Observer\UpdateProductQueue.php two times.
	2. These updated by column will insert logged in username of user.

	EP-6107: Remove Special price when Tier price set changed for product
	1. Added code to remove special price if tier price set is removed from the backend.
	2. Set special price as null if special price is equal to MSRP after calculation of special price as per tier price set rule.
	
	EP-6240: Price update backward compatibility for existing messages
	1. Added code to make existing code compatible to the older message queues.
		