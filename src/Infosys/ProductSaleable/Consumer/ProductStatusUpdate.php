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
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Model\Config;

/**
 * Class product stock update
 */
class ProductStatusUpdate
{
    protected ProductLogger $logger;

    protected Data $helper;

    protected Product $product;

    protected CollectionFactory $productCollectionFactory;

    private StockRegistryInterface $stockRegistry;

    protected Config $_eavConfig;

    /**
     * Constructor function
     *
     * @param ProductLogger $logger
     * @param Data $helper
     * @param Product $product
     * @param CollectionFactory $productCollectionFactory
     * @param StockRegistryInterface $stockRegistry
     * @param Config $_eavConfig
     */
    public function __construct(
        ProductLogger $logger,
        Data $helper,
        Product $product,
        CollectionFactory $productCollectionFactory,
        StockRegistryInterface $stockRegistry,
        Config $_eavConfig
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->product = $product;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->stockRegistry = $stockRegistry;
        $this->_eavConfig = $_eavConfig;
    }

    /**
     * @param string $stockConfig
     *
     * @return mixed|void
     */
    public function process($stockConfig): void
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
                $extendedAttributes = $product->getExtensionAttributes();
                $stockItem = $extendedAttributes->getStockItem();
                
                if($stockConfig){
                    $stockItem->setUseConfigManageStock(false);
                    $stockItem->setManageStock(true);
                    $stockItem->setIsInStock(0);
                } else {
                    if ($product->getSaleable() == 'Y') {
                        $stockItem->setUseConfigManageStock(true);
                        $stockItem->setManageStock(false);
                        $stockItem->setIsInStock(1);
                    } else {
                        $stockItem->setUseConfigManageStock(false);
                        $stockItem->setManageStock(true);
                        $stockItem->setIsInStock(0);
                    }
                }
                $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
                $this->logger->info("Product's stock updated successfully for SKU: ".$sku);
            }
        }
    }
}
