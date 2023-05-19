<?php

/**
 * @package   Infosys/ProductSaleable
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */
declare(strict_types=1);

namespace Infosys\ProductSaleable\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Infosys\ProductSaleable\Helper\Data;
use Infosys\ProductSaleable\Publisher\ProductStockStatus as Publisher;

/**
 * Class to update product stock after stock configuration change as per tier price set
 */
class StockStatusChange implements ObserverInterface
{
    protected Data $helper;

    private Publisher $publisher;

    /**
     * Constructor function
     *
     * @param Data $helper
     * @param Publisher $publisher
     */
    public function __construct(
        Data $helper,
        Publisher $publisher
    ) {
        $this->helper = $helper;
        $this->publisher = $publisher;
    }

    /**
     * Method to update product stock based on tier price set
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $stockConfig = $this->helper->getProductStockStatus();
        $this->publisher->publish($stockConfig);
    }
}
