# Module Infosys CreateWebsite

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CreateWebsite`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are creating website in bulk through the data patch.

	2. Added two columns (dealer_code and region_id) with table store_website where we store the dealer information like dealer code and region id.
	
	3. Create a new table toyota_dealer_regions where we insert the region code and region label
	
	4. Added Menu Toyota Regions on Store -> Settings -> Toyota Regions where we have a grid for toyota regions code
	    1. We can add new region.
		2. We have the ability to edit existing regions
		3. We have ability to delete the regions from grid
		 
    5.  Created two data patches for inserting the websites in bulk.
	    (Infosys\CreateWebsite\Setup\Patch\Data\CreateNewWebsite.php)
		(Infosys\CreateWebsite\Setup\Patch\Data\CreateWebsite.php)


