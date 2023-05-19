# Module Infosys ShippingRestriction

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_ShippingRestriction`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the SALES->Checkout->Allowed US States in Checkout
	- Here we can select required states and save it.

## Main Functionalities
	1. In this module, we are creating A Dropdownn for Allowed US States in Checkout because for Store wise default functionality was not working as expected for US States.
    2. Go to the SALES->Checkout->Allowed US States in Checkout and select states as per requirment.
    3. Now If you are selecting country as United States on Checkout frontend then it should load only our selected States for US.
	4. If you are trying to create an Order from Admin while adding address, select Country as United States and States should be load only our configuration selected states.
    5. We have created a graphQl call to send Active US states to AEM.
	6. The Functionality for GraphQl Call available in Infosys\ShippingRestriction\Model\Resolver\ShippingStates .