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
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Infosys\ProductSaleable\Logger\ProductLogger;
use Infosys\ProductSaleable\Helper\Data;

/**
 * Save stock status data from a product to the Stock Item
 *
 */
class ProductSaleable implements ObserverInterface
{
    private StockRegistryInterface $stockRegistry;

    private ProductLogger $logger;

    protected Data $helper;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param ProductLogger $logger
     * @param Data $helper
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        ProductLogger $logger,
        Data $helper
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Saving product stock status data
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
                $sku = $product->getSku();
                $extendedAttributes = $product->getExtensionAttributes();
                $stockItem = $extendedAttributes->getStockItem();
                $stockAAPConfig = $this->helper->getProductStockStatus();
                if($stockAAPConfig) {
                    $tierPriceSetOption = '';
                    $attribute = $product->getResource()->getAttribute('tier_price_set');
                    if ($attribute->usesSource()) {
                        $tierPriceSetOption = $attribute->getSource()->getOptionText($product->getTierPriceSet());
                    }
                    //Global manage stock is disable. so need to enable/disable before update the stock status
                    if ($product->getSaleable() == 'Y' && $tierPriceSetOption != 'AAP') {
                        $stockItem->setUseConfigManageStock(true);
                        $stockItem->setManageStock(false);
                        $stockItem->setIsInStock(1);
                    } else {
                        $stockItem->setUseConfigManageStock(false);
                        $stockItem->setManageStock(true);
                        $stockItem->setIsInStock(0);
                    }
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
                
                $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
                $this->logger->info("Product status updated successfully");
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
