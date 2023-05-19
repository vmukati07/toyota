# Module Infosys XtentoPdfCustomizer

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_XtentoPdfCustomizer`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Purpose
	This module developed to change the xtento default pdf template to custom pdf template with some new chnages.

## Main Functionalities
	1. Menu -> Stores -> PDF Customizer (Manage Pdf Templates).
	2. Click On Add Template and select type order from dropdown
	3. After click on order a template load popup will be open with default template.
	4. After opening this popup need to select the template Simple Order (Portrait) (Variant 2).
	5. Now need to click on Load Template Button, It will redirect to configuration page.
	6. Save template then check the template preview.

## Manaul testing
	- Go to Menu Sales -> Order
		- Select any order to check the template
		- Now Select the Action from Dropdown Print PDF : Order
		- Order Template will download

## Issue Fixed
	- Issue resolved for non existing array in total section related to discount.
	- Show frontend URL in footer section.
	- Remove extra column before product name.
	- Remove small gaps between each column.
	- Delivery Fee also needs to displayed.
	- Phone number should be displayed in same format as in Order details page.
	- Undefined Array or undefined variable issue fixed.
	- Mobile number is broken in Magento order details page in Sales Grid, invoice PDF and in emails.