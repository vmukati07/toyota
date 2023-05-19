# Module Infosys AttributeField

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_AttributeField`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
    1. In this module we are adding a attribute field for openning the filter in navigation block.

	2. We are adding a attribute field Auto Expand on attribute edit form (Store > Attributes > Product > Edit a Attribute > StoreFront Properties > Check "Auto Expanded" field).

	3. Added a field "flag" with varchar type on database table catalog_eav_attribute.

	4. Create a plugin to add Auto Expanded field on product attribute edit form (Infosys\AttributeField\Plugin\Block\Adminhtml\Product\Attribute\Edit\Tab\Front.php).


