<?php

/**
 * @package     Infosys/SalesReport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\SalesReport\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class to upgrade the stored procedure for sales report calculations
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Function to upgrade the stored procedure for sales report calculations
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $dropProcedure = "DROP PROCEDURE IF EXISTS calculateSalesStatistics";
            $setup->getConnection()->exec($dropProcedure);

            $createProcedureSql = "CREATE PROCEDURE calculateSalesStatistics (
                         IN storeId INT,
                         IN cDate DATE
            )
            BEGIN
            SELECT COUNT(sales_order.entity_id),
                ROUND(SUM(
                    sales_order.base_subtotal 
                    -
                    (
                        IFNULL(sales_order.base_subtotal_canceled,0)
                        + 
                        IFNULL(sales_order.base_subtotal_refunded,0)
                    )
                    ),2),
                ROUND(SUM(
                    IFNULL(sales_order.base_shipping_amount,0) 
                    - 
                    (
                        IFNULL(sales_order.base_shipping_refunded,0) 
                        + 
                        IFNULL(sales_order.base_shipping_canceled,0)
                    )
                    ),2),
                ROUND(SUM(
                    ABS(IFNULL(sales_order.base_discount_amount,0)) 
                    - 
                    (
                        ABS(IFNULL(sales_order.base_discount_refunded,0)) 
                        + 
                        ABS(IFNULL(sales_order.base_discount_canceled,0))
                    )
                    ),2)
            INTO @orderQuantity, @productSales, @shippingSales, @totalDiscount FROM sales_order WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId;
                 
            set @productCOGS = (SELECT ROUND(SUM(
                IFNULL(sales_order_item.base_cost,0)
                *
                (
                    sales_order_item.qty_ordered 
                    - 
                    (sales_order_item.qty_refunded + sales_order_item.qty_canceled)
                )
                ),2)
                FROM sales_order_item
                INNER JOIN sales_order ON sales_order.entity_id = sales_order_item.order_id
                WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId);

            set @shippingCOGS = (SELECT ROUND(SUM(
                IFNULL(df_sales_order_freight_recovery.freight_recovery,0)
                ),2) 
                FROM sales_order
                INNER JOIN df_sales_order_freight_recovery ON df_sales_order_freight_recovery.order_id = sales_order.entity_id
                WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId);     
             
            set @totalItemQuantity = (SELECT SUM(
                (sales_order_item.qty_ordered) 
                - 
                (sales_order_item.qty_refunded + sales_order_item.qty_canceled)   
                )
                FROM sales_order_item
                INNER JOIN sales_order ON sales_order.entity_id = sales_order_item.order_id
                WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId);  
     
            set @shippingGrossProfit = (@shippingSales - IFNULL(@shippingCOGS,0));    
                                 
            set @totalGrossSales =(@productSales + @shippingSales);

            set @totalNetSales =(@totalGrossSales -  IFNULL(@totalDiscount,0));

            set @productGrossProfit =(@productSales - @productCOGS);  
     
            set @totalGrossProfit =(@productGrossProfit + @shippingGrossProfit);                 
                    
            set @partsQuantity = (SELECT ROUND(SUM((sales_order_item.qty_ordered) - (sales_order_item.qty_refunded + sales_order_item.qty_canceled)),2)
                from sales_order
                INNER JOIN sales_order_item ON sales_order.entity_id = sales_order_item.order_id
                INNER JOIN catalog_product_entity ON catalog_product_entity.sku = sales_order_item.sku
                INNER JOIN eav_attribute_set ON eav_attribute_set.attribute_set_id = catalog_product_entity.attribute_set_id
                WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId AND eav_attribute_set.attribute_set_name = 'Service Part');
            
            set @accessoryQuantity =  (SELECT ROUND(SUM((sales_order_item.qty_ordered) - (sales_order_item.qty_refunded + sales_order_item.qty_canceled)),2)
                    from sales_order
                    INNER JOIN sales_order_item ON sales_order.entity_id = sales_order_item.order_id
                    INNER JOIN catalog_product_entity ON catalog_product_entity.sku = sales_order_item.sku
                    INNER JOIN eav_attribute_set ON eav_attribute_set.attribute_set_id = catalog_product_entity.attribute_set_id
                    WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId AND eav_attribute_set.attribute_set_name = 'Accessory');                
            
            set @timeToShip = (
                 SELECT AVG(ABS(timediff(sales_order.created_at,sales_shipment.created_at)/3600)) from sales_order INNER JOIN sales_shipment ON sales_order.entity_id=sales_shipment.order_id WHERE sales_order.status!='canceled' AND sales_order.status!='closed' AND DATE(sales_order.created_at) = cDate AND sales_order.store_id=storeId);    
     
            INSERT INTO toyota_dealer_sales_statistics (report_date, orders_qty, product_sales, shipping_sales, total_net_sales, total_gross_sales,product_gross_profit, shipping_gross_profit, total_gross_profit, total_discount, time_to_ship, store_id, parts_qty, accessory_qty, total_ordered_items_qty, shipping_cogs, product_cogs) 
            VALUES (cDate, @orderQuantity, @productSales, @shippingSales, @totalNetSales, @totalGrossSales, @productGrossProfit, @shippingGrossProfit, @totalGrossProfit, @totalDiscount, @timeToShip, storeId, @partsQuantity, @accessoryQuantity, @totalItemQuantity, @shippingCOGS, @productCOGS) 
            ON DUPLICATE KEY 
            UPDATE report_date = cDate, orders_qty = @orderQuantity, product_sales = @productSales, shipping_sales = @shippingSales, total_net_sales = @totalNetSales, total_gross_sales = @totalGrossSales, product_gross_profit = @productGrossProfit, shipping_gross_profit = @shippingGrossProfit, total_gross_profit = @totalGrossProfit, total_discount = @totalDiscount, time_to_ship = @timeToShip, store_id = storeId, parts_qty = @partsQuantity, accessory_qty = @accessoryQuantity, total_ordered_items_qty = @totalItemQuantity, shipping_cogs = @shippingCOGS, product_cogs = @productCOGS;
            END";
            $setup->getConnection()->exec($createProcedureSql);
        }
        $setup->endSetup();
    }
}
