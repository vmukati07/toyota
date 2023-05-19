# Module Infosys VehicleFitment

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_VehicleFitment`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA->Vehicle Fitment->General Settings
    - Logs: Enable/Disable, We can debug Vehicle Fitment Functionality by enabling this setting.

## Main Functionalities
	1. In this module, we have created this module to calculate vehicle fitment for product.
    2. For this we have created custom Indexer product_vehicle_fitment by using https://developer.adobe.com/commerce/php/development/components/indexing/custom-indexer/
    3. We can check FitmentIndexer in Infosys\VehicleSearch\Model\Indexer\VehicleIndexer .
    4. We are calculating vehicle fitment for all brands of a product and save in default store.
    5. We have created custom Logger so we can debug Vehicle Fitment Functionality by enabling this setting from Store Configuration.

## MySql Query for getting count of vehicle fitment data on database ##
 SELECT count(*) FROM `catalog_vehicle_entity`;

## MySql Query for getting those SKU which don't have mapping with vehicle fitment on database ##
 SELECT catalog_product_entity.entity_id, catalog_product_entity.sku FROM `catalog_product_entity`
 WHERE catalog_product_entity.entity_id NOT IN (SELECT catalog_vehicle_product.product_id FROM `catalog_vehicle_product`);

## MySql Query for getting vehicle fitment data for particular product on database ##
 SELECT catalog_vehicle_product.product_id, catalog_vehicle_entity.entity_id, catalog_vehicle_entity.model_year
 FROM `catalog_vehicle_product`
 INNER JOIN `catalog_vehicle_entity` ON catalog_vehicle_product.vehicle_id=catalog_vehicle_entity.entity_id where product_id = 295088;

## MySql Query for validating which product don't have the json data on database (On Staging: 'what_this_fits' attribute id = 356)##
 SELECT DISTINCT(entity_id) FROM `catalog_product_entity` where entity_id NOT IN
 (SELECT DISTINCT(catalog_product_entity.entity_id) FROM `catalog_product_entity` INNER JOIN `catalog_product_entity_text` ON
 catalog_product_entity.row_id = catalog_product_entity_text.row_id AND catalog_product_entity_text.attribute_id = 356);
