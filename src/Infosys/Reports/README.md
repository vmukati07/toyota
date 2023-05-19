# Module Infosys Reports

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_Reports`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Stores->Toyota->Reports->Report links
	- Text box to add links for Sales Performance (MBI) & Web Analytics (Adobe).
	- By default, the links added in default config xml will be displayed here.

## Main Functionalities
	1. In this module, we have created menu links inside Admin Reports section for MBI and Adobe analytics login page so that dealers (and corporate users) can easily navigate to these platforms.

	2. System Configuration created under Toyota tab for reports to add custom links for both sections.

	3. MBI report link added below Sales as "Sales Performance" and Adobe Analytics link added under Marketing as "Web Analytics".

	4. Link Access is based on ACL.

    5. For user with Admin Access Default Report links are configurable from admin.  User with admin access can modify these links. The default links  are added via config xml file and To provide/revoke access to these links for any specific user role from dashboard. The admin user should have admin rights go modify the access.

	6. Coupon reimbursement reports should include only coupons created by Toyota, So adding a new column "national_promotional_discount" in sales_order table to save product discounts of coupons/cart price rules created by Toyota. It will exclude discounts of coupons/cart price rules created by dealers and also shipping discounts.