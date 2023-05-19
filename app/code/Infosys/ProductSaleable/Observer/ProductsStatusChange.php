<?php
/**
 * @package Infosys/ProductSaleable
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ProductSaleable\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Infosys\ProductSaleable\Helper\Data;
use Infosys\ProductSaleable\Publisher\AapProductStatus as Publisher;

/**
 * Update product status based on config setting for all AAP product
 *
 */
class ProductsStatusChange implements ObserverInterface
{

    protected Data $helper;

    private Publisher $publisher;

    /**
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
     * Update product status based on config setting for all AAP product
     *
     * @param EventObserver $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(EventObserver $observer)
    {
        $statusConfig = $this->helper->getAapProductStatus();
        $this->publisher->publish($statusConfig);
    }
}
