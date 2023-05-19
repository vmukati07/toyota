<?php

/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PriceAdjustment\Consumer;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Infosys\PriceAdjustment\Logger\PriceCalculationLogger;
use Infosys\PriceAdjustment\Model\MediaFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Infosys\PriceAdjustment\Model\DealerPrice;
use Infosys\PriceAdjustment\Consumer\TierPriceSave;
/**
 * Provides a consumer for the 'magento.tier-price.handler' topic
 */
class TierPriceUpdate
{
    /** @var ProductRepositoryInterface */
    private CollectionFactory $productCollectionFactory;

    /** @var PriceCalculationLogger */
    private PriceCalculationLogger $logger;

    /** @var MediaFactory */
    protected MediaFactory $mediaFactory;

    /** @var Json */
    public Json $serializer;

    /** @var DealerPrice */
    protected DealerPrice $dealerPrice;

     /** @var TierPriceSave */
     protected TierPriceSave $tierPriceSave;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param PriceCalculationLogger $logger
     * @param MediaFactory $mediaFactory
     * @param Json $serializer
     * @param DealerPrice $dealerPrice
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        PriceCalculationLogger $logger,
        MediaFactory $mediaFactory,
        Json $serializer,
        DealerPrice $dealerPrice,
        TierPriceSave $tierPriceSave
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->mediaFactory = $mediaFactory;
        $this->serializer = $serializer;
        $this->dealerPrice = $dealerPrice;
        $this->tierPriceSave = $tierPriceSave;
    }

    /**
     * Responsible for processing a message in the 'magento.tier-price.handler' topic
     *
     * @param $dealerPriceUpdateSerializeData
     */
    public function process($dealerPriceUpdateSerializeData): void
    {
        $this->logger->info('TierPriceUpdate Import - entered process(…)');
        $dealerPriceUpdateProducts = $this->serializer->unserialize($dealerPriceUpdateSerializeData);

        if (isset($dealerPriceUpdateProducts[0]['sku']) || isset($dealerPriceUpdateProducts['sku']))
        {
            $skus = $dealerPriceUpdateProductArr = [];
            if(isset($dealerPriceUpdateProducts[0]['sku']))
            {
                foreach ($dealerPriceUpdateProducts as $dealerPriceUpdateProduct) {
                    $skus[] = $dealerPriceUpdateProduct['sku'];
                    $dealerPriceUpdateProductArr[$dealerPriceUpdateProduct['sku']] = $dealerPriceUpdateProduct['website'];
                }
            }
            else
            {
                $this->logger->info('TierPriceImport single skus from TierPriceUpdate for backward compatibility');
                $skus[] = $dealerPriceUpdateProducts['sku'];
                $dealerPriceUpdateProductArr[$dealerPriceUpdateProducts['sku']] = $dealerPriceUpdateProducts['website'];
            }
        }        
        else if(isset($dealerPriceUpdateProducts["tier_price_product_type"]))
        {
            $this->logger->info('TierPriceSave Called from TierPriceUpdate for backward compatibility');
            $tierMediaSet = $this->getDataForTierPriceSave($dealerPriceUpdateProducts);
            $tierMediaSet = $this->serializer->serialize($tierMediaSet);
            $this->tierPriceSave->process($tierMediaSet);
            return;
        }

        try {
            $regularPriceAttrId = $this->dealerPrice->getRegularPriceAttributeId();
            $costAttrId = $this->dealerPrice->getCostAttributeId();
            $tierPriceSetAttrId = $this->dealerPrice->getTierPriceSetAttrId();

            $productCollection = $this->productCollectionFactory->create();
            $productCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(['sku', 'row_id','attribute_set_id']);
            $productCollection->addFieldToFilter(
                'sku',
                $skus,
                'in'
            );

            $statement = $productCollection->getSelect();
            $statement->join(
                ['cst' => 'catalog_product_entity_decimal'],
                'e.row_id = cst.row_id',
                ['cost' => 'cst.value']
            );
            $statement->join(
                ['reg' => 'catalog_product_entity_decimal'],
                'e.row_id = reg.row_id',
                ['price' => 'reg.value']
            );
            $statement->join(
                ['tier' => 'catalog_product_entity_text'],
                'e.row_id = tier.row_id',
                ['tier_price_set' => 'tier.value']
            );
            $statement->where(
                'cst.attribute_id = ?',
                $costAttrId
            );
            $statement->where(
                'reg.attribute_id = ?',
                $regularPriceAttrId
            );
            $statement->where(
                'tier.attribute_id = ?',
                $tierPriceSetAttrId
            );

            $connection = $productCollection->getResource()->getConnection();
            $productData = $connection->fetchAssoc($statement);
            foreach ($productData as $product) {
                $website = $dealerPriceUpdateProductArr[$product['sku']];
                $websiteIds = explode(",", $website);
                if (count($websiteIds) == 0) continue;
                $specialPrices = $this->dealerPrice->getSpecialPrice($product, $websiteIds);
                if (count($specialPrices) > 0) {
                    $this->dealerPrice->setPricesPerStore($specialPrices);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('TierPriceUpdate failed!' . $e);
        }
    }

     /**
     * Prepare data to process Tier Price Save consumer as a backward compatibility which will process old messages which are in queue during deployment
     *
     * @param $dealerPriceUpdateData
     * @return array
     */
    public function getDataForTierPriceSave($dealerPriceUpdateProducts): array
    {
        $tierData = [];
        try{
            $tierData['website'] = $dealerPriceUpdateProducts["website"];
            $tierData['tier_price_product_type'] = $dealerPriceUpdateProducts["tier_price_product_type"];
            $tierData['tier_price_set'] = $dealerPriceUpdateProducts["tier_price_set"];
            $collection = $this->mediaFactory->create()->getCollection()->addFieldToSelect('entity_id');
            $collection->getSelect()
                        ->where('website = (?)', $tierData['website'])
                        ->where('media_set_selector = (?)', $tierData['tier_price_set'])
                        ->where('tier_price_product_type = (?)', $tierData['tier_price_product_type']);
            $tierPriceData = $collection->getData();
            if(isset($tierPriceData[0]['entity_id']))
            {
                $tierData['entity_id'] = $tierPriceData[0]['entity_id'];
            }
        }catch (\Exception $e) {
            $this->logger->error('TierPriceUpdate failed during backward compatibility!' . $e);
        }
        return $tierData;
    }
    
}
