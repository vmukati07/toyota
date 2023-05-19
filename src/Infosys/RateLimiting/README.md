# Module Infosys RateLimiting

	- [Installation](#markdown-header-installation)
	- [Configuration](#markdown-header-configuration)
	- [Main Functionalities](#markdown-header-main-functionalities)

## Installation

	- Enable the module by running `php bin/magento module:enable Infosys_RateLimiting`
	- Apply database updates by running `php bin/magento setup:upgrade` command
	- Generate static content for Admin html by running `php bin/magento setup:static-content:deploy -f --area adminhtml`
	- Flush the cache by running `php bin/magento cache:flush`

## Configuration

	- Store Configuration: Setup following store configuration under the Advanced->System->Full Page Cache->Fastly Configuration->Rate Limiting->Path Protection
	- Allow Protected Paths to be Cached: It's used to enable/disable Protected Paths to be Cached

## Main Functionalities

	1. In this module, we are modify the fastly module, so that it doesn't upload the VCL snippet named "magentomodule_rate_limiting_recv".  This snippet causes fastly to disable caching on the protected paths.

	2. Override below controller, and add condition for remove functionality to add and delete the VCL snippet if "Allow Protected Paths to be Cached" configuration is enabled.
	 - Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting\ToggleRateLimiting
	 - Fastly\Cdn\Controller\Adminhtml\FastlyCdn\RateLimiting\UpdatePaths
