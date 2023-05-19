# Module Infosys ExtendedOrderGridColumn

    ``infosys/module-extendedordergridcolumn``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
   Add some new column to Sales Order Grid
   # EP-1468: Display number of Items fulfilled in admin order grid.
      1. Add new column in order grid as Canceled Status to show the canceled item on order in format x of n items.
      2. Add new column in order grid as Pending Dealer Shipment Status to show the pending shipment item on order in format x of n items.
      3. Add new column in order grid as Direct FulFillment Status to show direct fulfillment item on order in format x of n items.
      4. Add new column in order grid as Return Approval Status to show the approved item on order in format x of n items.
      5. Add new column in order grid as Returned Status to show the returned item on order in format x of n items.
      6. Add new column in order grid as Shipping Status to show the shipped item on order in format x of n items.
         n of x, where
            n = number of products for which the action matching the column name has been taken
            x = number of products which can have that action taken.
            Example: 
               1 of 3
               2 of 10  
   # EP-5952 : Order Grid item count uses QTY of Line Items instead of QTY of items.
      Sales Order Grid item count uses QTY of total ordered items instead of QTY of Line Items.
## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Infosys`
 - Enable the module by running `php bin/magento module:enable Infosys_ExtendedOrderGridColumn`
 - Apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require infosys/module-extendedordergridcolumn`
 - enable the module by running `php bin/magento module:enable Infosys_ExtendedOrderGridColumn`
 - apply database updates by running `php bin/magento setup:upgrade`
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration



## Specifications



## Attributes



## Issues Fixed
   After placing an order, view the order in sales order grid and set Direct Fullfilment for order item.
   If we cancel order after sending to DF then in Pending DF column will be display 0 of x instead of n of x,
   and canceled column will be display n of x.
 