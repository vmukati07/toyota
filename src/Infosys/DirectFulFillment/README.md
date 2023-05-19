# Module Infosys DirectFulFillment

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_DirectFulFillment`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the TOYOTA->DirectFulfillment Configuration
	- Here we have configured Allowed US States in DirectFulfillment, Access Token settings and  Payload Integration settings.
	- We can also do settings to enable/disable DDOA Logging, API Connection Timeout, Back Order ETAs and Rejected Notification Emails.

## Main Functionalities
	1. In this module, Direct fulfillment is a feature where dealer can send an order to direct fulfillment so Toyota can fulfill that order.
	2. After placing an Order, we can see "Direct Fulfillment" button in Order level in Admin Sales Order Grid View Order link.
	3. After clicking on "Direct Fulfillment" button it redirect to direct fulfillment page and click the “Submit Directfulfillement” button. Send the magento order details to DDOA.
	4. When we click on "Direct Fulfillment" button it will open another window with ordered products and send to direct fulfillment as yes or no., we need to select yes and send order to direct fulfillment.
	5. "Direct Fulfillment" button will only be shown to Orders If Order Address details country configured in our Allowed US States in DirectFullfilment setting in Stores Configuration.
	6. SPAO Fillable and QUP are Product attributes will not be empty for an ordered product.
	7. We are using the observer method to check the directfulfillment eligibility status with respect to SPAO Fillable and QUP product attribute.
	8. We are using STORES->Configuration->TOYOTA->DirectFulfillment Configuration->Allowed US States in DirectFulfillment-> Select the Direct Fulfillment Export Profile and select option "Send Magento order to DDOA" to send the Magento order details to DDOA.
	9. Using the custom exportOrder class also we can use the request to  ddoa API to send the Order details to Toyota, that request we can see from SALES -> Sales Export -> Export Profiles Grid.
	10. We are using this profile and sending orders to direct fulfillment. If you open that profile and go to output format and select Output Format tab, we can see a xml format template.
	11. We will form a xml and send this xml to direct fulfillment api, and the action will be written in  Infosys\DirectFulFillment\Controller\Adminhtml\Fulfillment\Create.php 
	12. If you see this file, once an order is sent successfully to directfulfillment we are changing item level status as YES/No and you can see related functionality Logs in Sales -> Sales Export -> Execution log.
	13. we are using this file Infosys\DirectFulFillment\Model\Destination\ExportOrder.php to send an order.
	14. If Directfulfillment order item will be REJECTED or CANCELLED on that case order item will be shipped by manually or through shipstation.
			app\code\Infosys\DirectFulFillment\view\adminhtml\templates\shipping\create\default.phtml
 
-   # DF status update with Xento import/export modules
    1. Please refer this document for xtento https://support.xtento.com/wiki/Magento_Extensions:Magento_Product_Export_Module .
	2. Once order send to direct fulfillment any updates from direct fulfillment needs to be updated in order level, for this we have used import profiles.
	3. Any changes from direct fulfillment for an order they will generate a text file with order details and add those in a sftp.
	4. We will get those text files from that sftp and with the help of field mapping we are getting data for orders and updating them in Magento.
	5. We are creating some profile to update the order status on directfulfillment orders from SALES -> Tracking Import -> Import Profiles.
	6. If Edit profile, we have option to configure the text length on File Mapping -> File Mapping.
	7. This is the way we update a Direct fulfillment order.
	
	# DF failed Reports
    1. We have used Xtento module to create this report and the report is DF Failed Reports. With this we are exporting orders and send through an email.
	2. DF Failed Reports, we can find SALES -> Sales Export -> Export Profiles Grid and with Name "DF Failed Reports".
	3. We are exporting orders based on following conditions 
		1. Only DF orders needs to be exported (for this we have added a condition in xml as 
			<xsl:for-each select="orders/order">
			<xsl:if test="direct_fulfillment_status='1'"> ) 
		2. For order items also <xsl:for-each select="items/item">
		3. <xsl:if test="dealer_direct_fulfillment_status='1'">  dealer_direct_fulfillment_status  in sales_order_item tables needs to be 1.
		4. Based upon the notes logic we added in Infosys\DirectFulFillment\Helper\Xsl:: getNotes($orderItemId, $orderId)
		5. IF you see the logic inside this file, we are comparing orders with time
		6. Once an order is sent to directfulfilment we will wait for 4 hours to receive an acknowledgement, if we didn’t receive that we are exporting this order.
		7. After receiving acknowledgment if we didn’t get any updates from DF for 24 hours then we are exporting this order.
		8. If the order is backorder (stock is not available at that time and ETA date will be send from DF, we need to update that date in order item level)
		9. If shipment tracking number is not provided in order. based upon these conditions we need to generate a report.
	4. We can see Output format reference for "DF Failed Reports" from SALES -> Sales Export -> Export Profiles Grid and with Name "DF Failed Reports" and edit that Profile and select Output Format tab.

	5. In xtento we can use custom functions to get the dynamic data if data is not available in order table or order item table. If you check the output format in our report most of the fields are not available in order table so we used custom functions as 

	<xsl:value-of select="php:functionString('Infosys\DirectFulFillment\Helper\Xsl::getWebsiteUrl', ../store_id)"/><xsl:value-of select="$sepend" />

	6. If we want to use a custom function we need to add like this for a filed php:functionString('Infosys\DirectFulFillment\Helper\Xsl::getWebsiteUrl', ../store_id)" with input argument, here store_id is a input argument.
	
	7. We need to mention our custom function in etc/xtento/orderexport_settings.xml inside custom_allowed_php_functions tag Infosys\DirectFulFillment\Helper\Xsl::getDealerCode Here till Xsl is the class and getDealerCode is the function.

	8. With the help of these points, we created DF failed reports.

## Issues Fixed

	1. EP-5299: AC Admin - Use CSS to condense order page display
		- Added new class 'orderdata' in sections of sales order view page for remove whitespace using CSS.(Infosys\DirectFulFillment\view\adminhtml\templates\order\view\view.phtml, Infosys\DirectFulFillment\view\adminhtml\templates\order\view\info.phtml)