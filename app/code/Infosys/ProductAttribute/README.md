# Module Infosys ProductAttribute

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_ProductAttribute`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Main Functionalities
	1. In this module, we have created Resolver class AttributeLabel for dropdown attributes option lable. We are getting all dropdown attributes option label.

	2. We are checking if attribute code is weight or weight_type, set attribute as user defined.

	3. set attribute value for fitment data.

	4. set store config value for weight_type attribute.

	5. We have written method to get product fitment data based on store brand.

	6. We have created patch files to create Product Attributes.

	7. Graphql :
		type AttributeLabels @doc(description: "all dropdown Attributes to show in Product Details Page") {
			attribute_code : String  @doc(description: "all atributes code")
			attribute_label : String  @doc(description: "all atributes label")
			attribute_value : String  @doc(description: "all atributes value")
			visibility_status : String  @doc(description: "attribute visibility on frontend")
		}

## Enhancements
	1. EP-5631: Add new product Attribute for EPC H2 field within Adobe Commerce
		- Added new custom product attribute - Customer Copy Header in the 'PDP Custom Attributes' section. 
	2. EP-5463: Update "Substitution Parts Number" label
		- Updated the label for Substitution Parts Number to Supersessions in  'Supersession Part' section. 		