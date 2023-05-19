# Module Infosys DealerChanges

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_DealerChanges`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we are hiding some functionalities when any deler access the admin pannel

	2. We have created ACl permission where admin can choose this functionality for dealer that which dealer have the access functionalities.
	   (Infosys\DealerChanges\etc\acl.xml)

	3. Create a plugin for removing the hyperlink on Sygnifyd Decision column link on order grid for dealer login.
	   (Infosys\DealerChanges\Plugin\Listing\Columns\CaseLink.php)

	4. Add the Yotpo revieves tab on dashbard of Adobe commerce Admin.

	5. We are customizing Admin dashboard (Infosys\DealerChanges\Plugin\Backend\Block\Dashboard\Grid.php).