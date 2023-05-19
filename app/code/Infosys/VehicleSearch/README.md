# Module Infosys VehicleSearch

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_VehicleSearch`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA->Vehicle->Vehicle Aggregations->model_year_code Override	
	- Here If model_year_code will be Yes then model_year_code filter is passed into products graphql, ignore other vehicle filters.

## Main Functionalities
	1. In this module, We are creating a Search Index for Vehicles that almost similar to the Elastic search index created for product search.
	2. We are using this Elastic Search documentation to create an Index https://www.elastic.co/guide/en/elasticsearch/client/php-api/7.x/index_management.html
	2. How to test it: 
		1.	Run Magento reindex command:
			a.	php bin/magento indexer:reset
			b.	php bin/magento indexer:reindex

    3. After completion on Reindexing, Now check in Elastic search for new indexer (vehicle_indexer_index)
	   a. http://localhost:9200/_aliases - Here we can see Vehicle related Indexer.
	   b. http://localhost:9200/magento2_vehicle_indexer_index_1/_search  -  It will show all vehicles in store 1 and similar way we can get other store information based on the aliases name.


