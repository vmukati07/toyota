# Module Infosys PrimaryKeyFixes

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
   Updated datatype to below listed Table, Column Name
   # EP-EP-5806: Long term fix for "Numeric value out of range" error.
      The following DB tables have their data type int(11) updated to BIGINT(20).

      Table Name, Column Name

         catalog_product_entity_varchar, value_id
         catalog_product_entity_text, value_id
         catalog_product_entity_gallery, value_id
         catalog_product_entity_datetime, value_id
         catalog_product_entity_media_gallery_value_video, value_id
         catalog_product_link_attribute_decimal, value_id
         catalog_product_entity_media_gallery, value_id
         catalog_product_bundle_option_value, value_id
         catalog_product_entity_int, value_id
         catalog_product_entity_media_gallery_value_to_entity, value_id
         catalog_product_link_attribute_int, value_id
         catalog_product_entity_media_gallery_value, value_id
         catalog_product_entity_decimal, value_id
         catalog_product_link_attribute_varchar, value_id
         catalog_product_super_attribute_label, value_id
         catalog_product_entity_tier_price, value_id

         catalog_vehicle_product, entity_id

         catalog_category_product_cl, version_id
         vehicle_indexer_cl, version_id
         catalogrule_product_cl, version_id
         targetrule_product_rule_cl, version_id
         targetrule_rule_product_cl, version_id
         inventory_cl, version_id
         product_vehicle_facets_cl, version_id
         catalog_product_price_cl, version_id
         salesrule_rule_cl, version_id
         cataloginventory_stock_cl, version_id
         catalog_product_attribute_cl, version_id
         catalogrule_rule_cl, version_id
         customer_dummy_cl, version_id
         product_vehicle_fitment_cl, version_id
         design_config_dummy_cl, version_id
         catalogsearch_fulltext_cl, version_id
         catalog_product_category_cl, version_id
         mview_state, version_id

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Infosys`
 - Enable the module by running `php bin/magento module:enable Infosys_PrimaryKeyFixes`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - enable the module by running `php bin/magento module:enable Infosys_PrimaryKeyFixes`
 - apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration



## Specifications



## Attributes



## Issues Fixed
 EP-5806: Long term fix for "Numeric value out of range" error
   1. File : app\code\Infosys\PrimaryKeyFixes\etc\db_schema.xml
            Issue: Table catalog_vehicle_product column entity_id not changed to bigint
            Resolution : Removed below syntax from app\code\Infosys\PrimaryKeyFixes\etc\db_schema.xml
		      <table name="catalog_vehicle_product">
        		   <column xsi:type="bigint" name="entity_id" unsigned="true" comment="Entity ID"/>
    		   </table>
		      And changed entity_id type from int to bigint in Vehicle module.
            Path: app\code\Infosys\Vehicle\etc\db_schema.xml