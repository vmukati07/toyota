# Module Infosys OrderEmailTemplates

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_OrderEmailTemplates`
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Order Email Configuration -> Logs
	- Logs: It's used to enable/disable logs

## Main Functionalities
	1. In this module, we are creating a Order email template which will contain full details of order.

	2. We have to configure the dealer emailId.
	
	3. Observer OrderEmailTemplateVariables will help to add custom variables in order email templates

	4. In View section we have created template for invoice, order and shipment.

	5. We are using log to get the error report under /var/log/toyota_orderemailtemplate.log.
## Issues fixed
	1. Fix for Order confirmation email template when product is deleted to handle null check as per ticket EP-5278

	2. Implemented new Shipment email Tracking number link pointing to respective carrier pages tracking link.
	\app\code\Infosys\OrderEmailTemplates\view\frontend\templates\email\items\shipment\track.phtml

	3. Fix for multiple tracking info issue in shipment email templates, when we have done multiple shipments for one order as per the ticket EP-5937.


