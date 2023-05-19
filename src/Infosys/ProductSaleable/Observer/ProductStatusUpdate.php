<?php
/**
 * @package Infosys/ProductSaleable
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ProductSaleable\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Infosys\ProductSaleable\Logger\ProductLogger;
use Infosys\ProductSaleable\Helper\Data;

/**
 * Save update status data for a product
 *
 */
class ProductStatusUpdate implements ObserverInterface
{

    private ProductLogger $logger;

    protected Data $helper;

    /**
     * @param ProductLogger $logger
     * @param Data $helper
     */
    public function __construct(
        ProductLogger $logger,
        Data $helper
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Save update status data for a product
     *
     * @param EventObserver $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(EventObserver $observer)
    {
        try {
            /** @var Product $product */
            $product = $observer->getEvent()->getProduct();
            if ($product->getTypeId() != 'configurable') {
                //check if AAP Config value is YES/NO
                $aapProductsStatus = $this->helper->getAapProductStatus();
                if($aapProductsStatus) {
                    $tierPriceSet = '';
                    $attribute = $product->getResource()->getAttribute('tier_price_set');
                    if ($attribute->usesSource()) {
                        $tierPriceSet = $attribute->getSource()->getOptionText($product->getTierPriceSet());
                    }
                    if(isset($tierPriceSet) && $tierPriceSet == 'AAP') {
                        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED);
                    } else {
                        $product->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
