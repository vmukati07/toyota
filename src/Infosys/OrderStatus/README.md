# Mage2 Module Infosys OrderStatus

    ``infosys/module-orderstatus``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)



## Main Functionalities
Infosys_OrderStatus
We need to change the color of the order status in their Sales order grid.

Admin Menu → Sales → Orders

1. Pending - Yellow
2. Processing - Blue
3. On Hold - Grey
4. Complete - Green
5. Closed - Green
6. Canceled - Red

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Infosys`
 - Enable the module by running `php bin/magento module:enable Infosys_OrderStatus`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require infosys/module-orderstatus`
 - enable the module by running `php bin/magento module:enable Infosys_OrderStatus`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications




## Attributes



