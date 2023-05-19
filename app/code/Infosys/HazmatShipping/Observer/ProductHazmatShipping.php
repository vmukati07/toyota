<?php

/**
 * @package   Infosys/HazmatShipping
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\HazmatShipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Infosys\ProductSaleable\Helper\Data;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Event\Observer;
use Infosys\ProductSaleable\Logger\ProductLogger;

/**
 * Class to update shipperhq shipping group and status of a product
 */
class ProductHazmatShipping implements ObserverInterface
{

    /**
     * @var Action
     */
    protected Action $action;

    /**
     * @var ProductLogger
     */
    private ProductLogger $logger;

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * Constructor function
     *
     * @param Product $product
     * @param ProductLogger $logger
     * @param Data $helper
     */
    public function __construct(
        Action $action,
        ProductLogger $logger,
        Data $helper
    ) {
        $this->action = $action;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Method to update Product Attributes
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        try {
            $product = $observer->getEvent()->getProduct();
            $stores = $product->getStoreIds();
            array_unshift($stores, '0');

            //Updating shipperhq shipping group based on hazmat flag
            $hazmatFlag = $product->getHazmatFlag();
            $doNotShip = $product->getDoNotShip();

            if ($hazmatFlag == 'Y' || $doNotShip== 'Y') {
                $optionData = $this->getOptionId($product,'Do Not Ship');
            } else {
                $optionData = $this->getOptionId($product,'');
            }
            $product->setData('shipperhq_shipping_group', $optionData);
            
            //Updating Product Status based on threshold price
            $threshold_price = $this->helper->getThresholdPrice();
            if ($threshold_price) {
                if ($product->getPrice() <= $threshold_price) {
                    $product->setStatus(Status::STATUS_DISABLED);
                    $updateAttributes['status'] = Status::STATUS_DISABLED;
                    foreach ($stores as $storeId) {
                        $this->action->updateAttributes([$product->getId()], $updateAttributes, $storeId);
                    }
                } else {
                    $product->setStatus(Status::STATUS_ENABLED);
                    $updateAttributes['status'] = Status::STATUS_ENABLED;
                    foreach ($stores as $storeId) {
                        $this->action->updateAttributes([$product->getId()], $updateAttributes, $storeId);
                    }
                }
            }
            $product->getResource()->saveAttribute($product, 'shipperhq_shipping_group');
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Method to get option id
     *
     * @param string $text
     */
    public function getOptionId($product,$text): string
    {
        $attribute = $product->getResource()->getAttribute('shipperhq_shipping_group');
        if ($attribute->usesSource()) {
            $optionID = $attribute->getSource()->getOptionId($text);
        }
        return $optionID;
    }
}
