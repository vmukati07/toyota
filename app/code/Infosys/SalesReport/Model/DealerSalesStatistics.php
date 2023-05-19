<?php

/**
 * @package Infosys/SalesReport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\SalesReport\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Infosys\SalesReport\Api\Data\DealerSalesStatisticsInterface;

/**
 * Model class for toyota dealer sales statistics table
 */
class DealerSalesStatistics extends AbstractExtensibleModel implements DealerSalesStatisticsInterface
{
    /**
     * Initialize Resource Model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\DealerSalesStatistics::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return parent::getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($entityId)
    {
        return $this->setData(self::ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getReportDate()
    {
        return parent::getData(self::DATE);
    }

    /**
     * @inheritDoc
     */
    public function setReportDate($date)
    {
        return $this->setData(self::DATE, $date);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId()
    {
        return parent::getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getOrdersQty()
    {
        return parent::getData(self::ORDERS_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setOrdersQty($ordersQty)
    {
        return $this->setData(self::ORDERS_QTY, $ordersQty);
    }

    /**
     * @inheritDoc
     */
    public function getProductSales()
    {
        return parent::getData(self::PRODUCT_SALES);
    }

    /**
     * @inheritDoc
     */
    public function setProductSales($productSales)
    {
        return $this->setData(self::PRODUCT_SALES, $productSales);
    }

    /**
     * @inheritDoc
     */
    public function getShippingSales()
    {
        return parent::getData(self::SHIPPING_SALES);
    }

    /**
     * @inheritDoc
     */
    public function setShippingSales($shippingSales)
    {
        return $this->setData(self::SHIPPING_SALES, $shippingSales);
    }

    /**
     * @inheritDoc
     */
    public function getTotalNetSales()
    {
        return parent::getData(self::TOTAL_NET_SALES);
    }

    /**
     * @inheritDoc
     */
    public function setTotalNetSales($totalNetSales)
    {
        return $this->setData(self::TOTAL_NET_SALES, $totalNetSales);
    }

    /**
     * @inheritDoc
     */
    public function getTotalGrossSales()
    {
        return parent::getData(self::TOTAL_GROSS_SALES);
    }

    /**
     * @inheritDoc
     */
    public function setTotalGrossSales($totalGrossSales)
    {
        return $this->setData(self::TOTAL_GROSS_SALES, $totalGrossSales);
    }

    /**
     * @inheritDoc
     */
    public function getProductGrossProfit()
    {
        return parent::getData(self::PRODUCT_GROSS_PROFIT);
    }

    /**
     * @inheritDoc
     */
    public function setProductGrossProfit($productGrossProfit)
    {
        return $this->setData(self::PRODUCT_GROSS_PROFIT, $productGrossProfit);
    }

    /**
     * @inheritDoc
     */
    public function getShippingGrossProfit()
    {
        return parent::getData(self::SHIPPING_GROSS_PROFIT);
    }

    /**
     * @inheritDoc
     */
    public function setShippingGrossProfit($shippingGrossProfit)
    {
        return $this->setShippingGrossProfit(self::SHIPPING_GROSS_PROFIT, $shippingGrossProfit);
    }

    /**
     * @inheritDoc
     */
    public function getTotalGrossProfit()
    {
        return parent::getData(self::TOTAL_GROSS_PROFIT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalGrossProfit($totalGrossProfit)
    {
        return $this->setData(self::TOTAL_GROSS_PROFIT, $totalGrossProfit);
    }

    /**
     * @inheritDoc
     */
    public function getTotalDiscount()
    {
        return parent::getData(self::TOTAL_DISCOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalDiscount($totalDiscount)
    {
        return $this->setData(self::TOTAL_DISCOUNT, $totalDiscount);
    }

    /**
     * @inheritDoc
     */
    public function getTimeToReceive()
    {
        return parent::getData(self::TIME_TO_RECEIVE);
    }

    /**
     * @inheritDoc
     */
    public function setTimeToReceive($timeToReceive)
    {
        return $this->setData(self::TIME_TO_RECEIVE, $timeToReceive);
    }

    /**
     * @inheritDoc
     */
    public function getTimeToShip()
    {
        return parent::getData(self::TIME_TO_SHIP);
    }

    /**
     * @inheritDoc
     */
    public function setTimeToShip($timeToShip)
    {
        return $this->setData(self::TIME_TO_SHIP, $timeToShip);
    }

    /**
     * @inheritDoc
     */
    public function getPartsQty()
    {
        return parent::getData(self::PARTS_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setPartsQty($partsQty)
    {
        return $this->setData(self::PARTS_QTY, $partsQty);
    }

    /**
     * @inheritDoc
     */
    public function getAcccessoryQty()
    {
        return parent::getData(self::ACCESSORY_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setAcccessoryQty($acccessoryQty)
    {
        return $this->setData(self::ACCESSORY_QTY, $acccessoryQty);
    }

    /**
     * @inheritDoc
     */
    public function getTotalOrderedItemsQty()
    {
        return parent::getData(self::TOTAL_ORDERED_ITEMS_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setTotalOrderedItemsQty($totalOrderedItemsQty)
    {
        return $this->setData(self::TOTAL_ORDERED_ITEMS_QTY, $totalOrderedItemsQty);
    }

    /**
     * @inheritDoc
     */
    public function getShippingCogs()
    {
        return parent::getData(self::SHIPPING_COGS);
    }

    /**
     * @inheritDoc
     */
    public function setShippingCogs($shippingCogs)
    {
        return $this->setData(self::SHIPPING_COGS, $shippingCogs);
    }

    /**
     * @inheritDoc
     */
    public function getProductCogs()
    {
        return parent::getData(self::PRODUCT_COGS);
    }

    /**
     * @inheritDoc
     */
    public function setProductCogs($productCogs)
    {
        return $this->setData(self::PRODUCT_COGS, $productCogs);
    }
}
