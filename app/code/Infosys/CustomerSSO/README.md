# Module Infosys CustomerSSO

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_CustomerSSO`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Configuration->Services->SAML SSO for admins (backend) and SAML SSO for customers
	- We have two setting there one for admin user login through the SSO (SAML SSO for admins (backend)) and for customer login fron frontend (SAML SSO for customers).
	- Have setting for Indentity Provider so set here some info related to the IdP that will be connected with our Magento

## Main Functionalities

	1. Added SAML Single Sign-On where customer can login through it.
	
	2. In this module we are customising the functionality for Pitbulk_SAML2 module.

	3. Through this module customer can Login via Identity provider with the customer login form.

	4. We have created a customer attribute "DCS Guid" throught he data patches.
	   (Infosys\CustomerSSO\Setup\Patch\Data\AddCustomerAttribute.php)
	   (Infosys\CustomerSSO\Setup\Patch\Data\UpdateCustomerAttribute.php)
	
	5. For getting customer token (getCustomerToken()) and updating customer information in SSO through the curl request (updateCustomerDetails()).
	
	6. Create resolver file to activate and update customer request processing through the graphql.

    7. We have the ability to update customer information, update phone number, verify the phone numbers from the graphql request.
	
	8. We have customise the Pitbulk_SAML2 login and logout functionality so we have override the file.
	   (Infosys\CustomerSSO\Controller\Saml2\Login.php)
	   (Infosys\CustomerSSO\Controller\Saml2\Logout.php)

## Issues Fixed
	# EP-1468: Security: Graphql mutations for UpdateCustomerPhoneNumber and ChangeCustomerEmail should only be available as POST reqeusts.
		1. Changed the changeCustomerEmail graphql query to mutation for secutity reaseon so that it can be called using POST Method.
		2. Changed the updateCustomerPhoneNumber graphql query to mutation for secutity reaseon so that it can be called using POST Method.
	# EP-5478: Updating Users name in my account shows success when inputting invalid password.
		1. Returned error message along with old user info when user entered wrong password while updating user name in my account section.
		2. Returned DCS error response while generating DCS API token in case of user entered wrong password.