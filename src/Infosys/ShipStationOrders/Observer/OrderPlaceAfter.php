<?php

/**
 * @package     Infosys/ShipStationOrders
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ShipStationOrders\Observer;

use Magento\Framework\Event\ObserverInterface;
use Infosys\ShipStationOrders\Logger\ShipStationOrdersLogger;
use Magento\Framework\Event\Observer;

/**
 *  SalesOrderPlaceAfterObserver Observer.
 */
class OrderPlaceAfter implements ObserverInterface
{
    protected ShipStationOrdersLogger $logger;

    /**
     * Constructor function
     *
     * @param ShipStationOrdersLogger $logger
     */
    public function __construct(
        ShipStationOrdersLogger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Observer to set is store pickup flag value on order
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $order = $observer->getEvent()->getOrder();
            if ($order->getShippingMethod() == 'dealerstore_pickup') {
                $order->setIsStorePickup(1)->save();
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
