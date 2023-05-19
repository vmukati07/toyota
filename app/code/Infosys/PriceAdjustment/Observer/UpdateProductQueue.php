<?php
/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
namespace Infosys\PriceAdjustment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Infosys\PriceAdjustment\Logger\PriceCalculationLogger;
use Infosys\PriceAdjustment\Model\DealerPrice;
class UpdateProductQueue implements ObserverInterface
{
    protected PriceCalculationLogger $logger;

    /** @var DealerPrice */
    protected DealerPrice $dealerPrice;
    
    /**
     * @param PriceCalculationLogger $logger
     * @param DealerPrice $dealerPrice
     */
    public function __construct(
        PriceCalculationLogger $logger,
        DealerPrice $dealerPrice
    ) {
        $this->logger = $logger;
        $this->dealerPrice = $dealerPrice;
    }
    
    /**
     * Execute function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $productData = [];
            $product = $observer->getEvent()->getProduct();
            $websiteIds = $product->getWebsiteIds();
            $productData['sku'] = $product->getSku();
            $productData['row_id'] = $product->getRowId();
            $productData['tier_price_set'] = $product->getTierPriceSet();
            $productData['attribute_set_id'] = $product->getAttributeSetId();
            $productData['price'] = $product->getPrice();
            $productData['cost'] = $product->getCost();
            if ($product) {
                $specialPrices = $this->dealerPrice->getSpecialPrice($productData, $websiteIds);
                if (count($specialPrices) > 0) {
                    $this->dealerPrice->setPricesPerStore($specialPrices);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error while updating special price after product save " . $e);
        }
    }   
}
