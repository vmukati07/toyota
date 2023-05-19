<?php

/**
 * @package     Infosys/ProductSaleable
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ProductSaleable\Consumer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Infosys\ProductSaleable\Helper\Data;
use Infosys\ProductSaleable\Logger\ProductLogger;
use Magento\Eav\Model\Config;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Action;

/**
 * Consumer class for update product status based on config setting
 */
class AapProductStatus
{
    protected ProductLogger $logger;

    protected Data $helper;

    protected Product $product;

    protected CollectionFactory $productCollectionFactory;

    protected Config $_eavConfig;

    protected Action $action;

    /**
     * Constructor function
     *
     * @param ProductLogger $logger
     * @param Data $helper
     * @param Product $product
     * @param CollectionFactory $productCollectionFactory
     * @param Config $_eavConfig
     * @param Action $action
     */
    public function __construct(
        ProductLogger $logger,
        Data $helper,
        Product $product,
        CollectionFactory $productCollectionFactory,
        Config $_eavConfig,
        Action $action
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->product = $product;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->_eavConfig = $_eavConfig;
        $this->action = $action;
    }

    /**
     * @param string $statusConfig
     *
     * @return mixed|void
     */
    public function process($statusConfig): void
    {
        $attribute = $this->_eavConfig->getAttribute('catalog_product', 'tier_price_set');
        $options = $attribute->getSource()->getAllOptions();
        $optionId = '';
        $productIds = array();
        foreach ($options as $option) {
            if ($option['label'] == 'AAP') {
                $optionId = $option['value'];
                break;
            }
        }
        $this->logger->info("Tier price set AAP option Id: " . $optionId);
        if(!empty($optionId)){
            $productIds = $this->productCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('tier_price_set', $optionId)
                    ->getColumnValues('entity_id');
        }
        $this->logger->info("AAP Product Ids " . json_encode($productIds));
        if ($productIds) {
            foreach ($productIds as $productId) {
                $product = $this->product->load($productId);
                $sku = $product->getSku();
                $stores = $product->getStoreIds();
                array_unshift($stores, '0');
                if($statusConfig) {
                    $updateAttributes['status'] = Status::STATUS_DISABLED;
                } else {
                    $productPrice = $product->getPrice();
                    $thresholdPrice = $this->helper->getThresholdPrice();
                    $isBelowThreshold = isset($thresholdPrice) && $productPrice <= $thresholdPrice;// If price is below threshold, always disable
                    
                    if ($isBelowThreshold) {
                        $updateAttributes['status'] = Status::STATUS_DISABLED;
                    } else {
                        $updateAttributes['status'] = Status::STATUS_ENABLED;
                    }
                }
                foreach ($stores as $storeId) {
                    $this->action->updateAttributes([$product->getId()], $updateAttributes, $storeId);
                }
                $this->logger->info("AAP Product's status updated successfully for SKU: ".$sku);
            }
        }
    }
}
