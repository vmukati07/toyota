# Module Infosys PaymentConfigGraphQL

	- [Installation](#markdown-header-installation)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_PaymentConfigGraphQL`
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Go to Stores> Configuration> Toyota> Stripe payment configuration and Enable/Disable old endpoint for graphql.

## Main Functionalities
	1. In this module, we have created custom GraphQL for dealer payment configuration.

	2. Graphql V1(with authorization token) :
		{
			storePaymentConfig
			{
				pub_api_key
				secret_api_key
			}
		}

	3. GraphQL V2(without authorization token) :
		{
			storePaymentConfigV2
			{
				pub_api_key
			}
		}