<?php

/**
 * @package Infosys/SalesReport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\SalesReport\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Toyota dealer sales statistics table interface
 */
interface DealerSalesStatisticsInterface extends ExtensibleDataInterface
{

    const TOYOTA_DEALER_SALES_STATISTICS_TABLE = 'toyota_dealer_sales_statistics';

    const ID = 'entity_id';

    const DATE = 'report_date';

    const STORE_ID = 'store_id';

    const ORDERS_QTY = 'orders_qty';

    const PRODUCT_SALES = 'product_sales';

    const SHIPPING_SALES = 'shipping_sales';

    const TOTAL_NET_SALES = 'total_net_sales';

    const TOTAL_GROSS_SALES = 'total_gross_sales';

    const PRODUCT_GROSS_PROFIT = 'product_gross_profit';

    const SHIPPING_GROSS_PROFIT = 'shipping_gross_profit';

    const TOTAL_GROSS_PROFIT = 'total_gross_profit';

    const TOTAL_DISCOUNT = 'total_discount';

    const TIME_TO_RECEIVE = 'time_to_receive';

    const TIME_TO_SHIP = 'time_to_ship';

    const PARTS_QTY = 'parts_qty';

    const ACCESSORY_QTY = 'accessory_qty';

    const TOTAL_ORDERED_ITEMS_QTY = 'total_ordered_items_qty';

    const SHIPPING_COGS = 'shipping_cogs';

    const PRODUCT_COGS = 'product_cogs';

    /**
     * Entity id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set entity id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Dealer sales statistics date
     *
     * @return string
     */
    public function getReportDate();

    /**
     * Set sales statistics date
     *
     * @param string $date
     * @return $this
     */
    public function setReportDate($date);

    /**
     * Store id
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set Store id
     *
     * @param id $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Orders qty
     *
     * @return int|null
     */
    public function getOrdersQty();

    /**
     * Set orders qty
     *
     * @param int $ordersQty
     * @return $this
     */
    public function setOrdersQty($ordersQty);

    /**
     * Product sales
     *
     * @return string|null
     */
    public function getProductSales();
    /**
     * Set product sales
     *
     * @param string $productSales
     * @return $this
     */
    public function setProductSales($productSales);

    /**
     * Shipping sales
     *
     * @return string|null
     */
    public function getShippingSales();

    /**
     * Set shipping sales
     *
     * @param string $shippingSales
     * @return $this
     */
    public function setShippingSales($shippingSales);

    /**
     * Total net sales
     *
     * @return string|null
     */
    public function getTotalNetSales();

    /**
     * Set total net sales
     *
     * @param string $totalNetSales
     * @return $this
     */
    public function setTotalNetSales($totalNetSales);

    /**
     * Total gross sales
     *
     * @return string|null
     */
    public function getTotalGrossSales();

    /**
     * Set total gross sales
     *
     * @param string $totalGrossSales
     * @return $this
     */
    public function setTotalGrossSales($totalGrossSales);

    /**
     * Product gross profit
     *
     * @return string|null
     */
    public function getProductGrossProfit();

    /**
     * Set product gross profit
     *
     * @param string $productGrossProfit
     * @return $this
     */
    public function setProductGrossProfit($productGrossProfit);

    /**
     * Shipping gross profit
     *
     * @return string|null
     */
    public function getShippingGrossProfit();

    /**
     * Set shipping gross profit
     *
     * @param string $shippingGrossProfit
     * @return $this
     */
    public function setShippingGrossProfit($shippingGrossProfit);

    /**
     * Total Gross Profit
     *
     * @return string|null
     */
    public function getTotalGrossProfit();

    /**
     * Set total gross profit
     *
     * @param string $totalGrossProfit
     * @return $this
     */
    public function setTotalGrossProfit($totalGrossProfit);

    /**
     * Total discount
     *
     * @return string|null
     */
    public function getTotalDiscount();

    /**
     * Set total discount
     *
     * @param string $totalDiscount
     * @return $this
     */
    public function setTotalDiscount($totalDiscount);

    /**
     * Time to receive
     *
     * @return int|null
     */
    public function getTimeToReceive();

    /**
     * Set time to receive
     *
     * @param int $timeToReceive
     * @return $this
     */
    public function setTimeToReceive($timeToReceive);

    /**
     * Time to ship
     *
     * @return int|null
     */
    public function getTimeToShip();

    /**
     * Set time to ship
     *
     * @param int $timeToShip
     * @return $this
     */
    public function setTimeToShip($timeToShip);

    /**
     * Parts quantity
     *
     * @return string|null
     */
    public function getPartsQty();

    /**
     * Set parts quantity
     *
     * @param string $partsQty
     * @return $this
     */
    public function setPartsQty($partsQty);

    /**
     * Acccessory quantity
     *
     * @return string|null
     */
    public function getAcccessoryQty();

    /**
     * Set acccessory quantity
     *
     * @param string $acccessoryQty
     * @return $this
     */
    public function setAcccessoryQty($acccessoryQty);

    /**
     * Total ordered items quantity
     *
     * @return string|null
     */
    public function getTotalOrderedItemsQty();

    /**
     * Set parts quantity
     *
     * @param string $totalOrderedItemsQty
     * @return $this
     */
    public function setTotalOrderedItemsQty($totalOrderedItemsQty);

    /**
     * Shipping cogs
     *
     * @return string|null
     */
    public function getShippingCogs();

    /**
     * Set shipping cogs
     *
     * @param string $shippingCogs
     * @return $this
     */
    public function setShippingCogs($shippingCogs);

    /**
     * Product cogs
     *
     * @return string|null
     */
    public function getProductCogs();

    /**
     * Set product cogs
     *
     * @param string $productCogs
     * @return $this
     */
    public function setProductCogs($productCogs);
}
