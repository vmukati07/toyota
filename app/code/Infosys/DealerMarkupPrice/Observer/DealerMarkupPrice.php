<?php

/**
 * @package     Infosys/DealerMarkupPrice
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerMarkupPrice\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use ShipperHQ\Shipper\Helper\Data as ShipperHQDataHelper;

/**
 * Class to save dealer markup price on orders
 */
class DealerMarkupPrice implements ObserverInterface
{
    /**
     * @var ShipperHQDataHelper
     */
    protected ShipperHQDataHelper $shipperDataHelper;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var CartRepositoryInterface
     */
    protected CartRepositoryInterface $quoteRepository;

    /**
     * Constructor function
     *
     * @param ShipperHQDataHelper $shipperDataHelper
     * @param LoggerInterface $logger
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        ShipperHQDataHelper $shipperDataHelper,
        LoggerInterface $logger,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->shipperDataHelper = $shipperDataHelper;
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Method to save dealer markup price on orders
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $order = $observer->getEvent()->getOrder();
            if ($order->getId()) {
                $quoteId = $order->getQuoteId();
                /** @var Magento\Quote\Api\CartRepositoryInterface */
                $quote = $this->quoteRepository->get($quoteId, [$order->getStoreId()]);
                $shippingAddress = $quote->getShippingAddress();
                $shippingMethod = (string) $order->getShippingMethod();
                $shippingRate = $shippingAddress->getShippingRateByCode($shippingMethod);
                if ($shippingRate) {
                    $carrierGroupDetails = $shippingRate->getData('carriergroup_shipping_details');
                    if (!empty($carrierGroupDetails)) {
                        $carrierDetails = $this->shipperDataHelper->decodeShippingDetails($carrierGroupDetails);
                        $dealerMarkup = $carrierDetails['price'] - $carrierDetails['cost'];
                        $order->setDealerMarkupPrice($dealerMarkup);
                        $order->save();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error when saving dealer markup price: " . $e);
        }
    }
}
