<?php

/**
 * @package Infosys/PriceAdjustment
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\PriceAdjustment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Infosys\PriceAdjustment\Logger\PriceCalculationLogger;
use Infosys\PriceAdjustment\Publisher\TierPriceImport as Publisher;
use Magento\Framework\Serialize\Serializer\Json;

/**
 *  Class to publish data to rabbitmq while importing products
 */

class ProductQueue implements ObserverInterface
{

    protected PriceCalculationLogger $logger;

    public Json $serializer;

    private Publisher $publisher;

    /**
     * Construct
     *
     * @param PriceCalculationLogger $logger
     * @param Json $serializer
     * @param Publisher $publisher
     */
    public function __construct(
        PriceCalculationLogger $logger,
        Json $serializer,
        Publisher $publisher
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->serializer = $serializer;
    }

    /**
     * Execute function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        $bunch = $observer->getEvent()->getBunch();
        $websiteData = $observer->getEvent()->getWebsiteData();
        $dealerPriceUpdateData = [];
        foreach ($bunch as $rowNum => $rowData) {
            //skip if dealer price update not required
           if (!$rowData['update_dealer_price']) continue;

            //assign existing website ids if `assign all products to store` config value set as no
            $websiteIds = '';
            $sku = $rowData['sku'];
            if (isset($rowData['product_website_ids'])) {
                $websiteIds = $rowData['product_website_ids'];
            } elseif (isset($websiteData[$sku]['website_ids'])) {
                $websiteIds = $websiteData[$sku]['website_ids'];
            }
            if (!empty($websiteIds)) {
                $tierMediaSet = [];
                $tierMediaSet['website'] = $websiteIds;
                $tierMediaSet['sku'] = $rowData['sku'];
                $dealerPriceUpdateData[] = $tierMediaSet;
            }
        }
        if (!empty($dealerPriceUpdateData)) {
            //process dealer price update using rabbitmq
            $dealerPriceUpdateSerializeData = $this->serializer->serialize($dealerPriceUpdateData);
            $this->logger->info("publishing to queue--" . $dealerPriceUpdateSerializeData);
            $this->publisher->publish($dealerPriceUpdateSerializeData);
        }
    }
}
