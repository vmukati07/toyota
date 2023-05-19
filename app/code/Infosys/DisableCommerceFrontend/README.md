# Module Infosys DisableCommerceFrontend

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_DisableCommerceFrontend`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores -> Toyota -> Disable Commerce Frontend -> Disable Commerce Frontend
	- Have option to disable Disable Commerce Frontend
	- And also Disable Commerce Homepage

## Main Functionalities

	1. Main functionality of this module is to redirect frontend to AEM frontend if commerce frontend is disable so once  we need to acees the store then it redirect to automatic to AEM storefront.

	2. Redirect to AEM fronend homepage if magento frontend homepage has been disabled from configuration (Infosys\DisableCommerceFrontend\Plugin\Cms\Controller\Index\Index.php).

	3. Redirect to magento customer pages to AEM frontend like login, registration, forgetpassword, resetpassword confirmation page etc.

	4. Customer can checkout through the AEM frontend if magento frontend is disable from the admin configuration in this case customer redirect to AEM fronend to place an order.


