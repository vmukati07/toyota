<?php

/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PriceAdjustment\Consumer;

use Infosys\PriceAdjustment\Model\Config\Configuration;
use Infosys\PriceAdjustment\Model\StoreAndProductDimensionReindexer;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Infosys\PriceAdjustment\Logger\PriceCalculationLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Model\StoreManagerInterface;
use Infosys\PriceAdjustment\Model\MediaFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ResourceModel\Product\Price\SpecialPrice;
use Magento\Catalog\Api\Data\SpecialPriceInterfaceFactory;
use Infosys\PriceAdjustment\Model\DealerPrice;

/**
 * Provides a consumer for the 'magento.tier-price.save' topic
 */
class TierPriceSave
{
    /** @var CollectionFactory */
    protected CollectionFactory $productCollectionFactory;

    /** @var PriceCalculationLogger */
    private PriceCalculationLogger $logger;

    /** @var StoreManagerInterface */
    private StoreManagerInterface $storeManager;

    /** @var MediaFactory */
    protected MediaFactory $mediaFactory;

    /** @var Json */
    public Json $serializer;

    /** @var SearchCriteriaBuilder */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /** @var SpecialPrice */
    private SpecialPrice $specialPrice;

    /** @var SpecialPriceInterfaceFactory */
    protected SpecialPriceInterfaceFactory $specialPriceFactory;

    /** @var Configuration */
    private Configuration $configuration;

    /** @var StoreAndProductDimensionReindexer */
    private StoreAndProductDimensionReindexer $storeAndProductDimensionReindexer;

    /** @var IndexerRegistry */
    private IndexerRegistry $indexerRegistry;

    /** @var DealerPrice */
    protected DealerPrice $dealerPrice;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param PriceCalculationLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param SpecialPrice $specialPrice
     * @param MediaFactory $mediaFactory
     * @param Json $serializer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SpecialPriceInterfaceFactory $specialPriceFactory
     * @param Configuration $configuration
     * @param StoreAndProductDimensionReindexer $storeAndProductDimensionReindexer
     * @param IndexerRegistry $indexerRegistry
     * @param DealerPrice $dealerPrice
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        PriceCalculationLogger $logger,
        StoreManagerInterface $storeManager,
        SpecialPrice $specialPrice,
        MediaFactory $mediaFactory,
        Json $serializer,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SpecialPriceInterfaceFactory $specialPriceFactory,
        Configuration $configuration,
        StoreAndProductDimensionReindexer $storeAndProductDimensionReindexer,
        IndexerRegistry $indexerRegistry,
        DealerPrice $dealerPrice
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->specialPrice = $specialPrice;
        $this->mediaFactory = $mediaFactory;
        $this->serializer = $serializer;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->specialPriceFactory = $specialPriceFactory;
        $this->configuration = $configuration;
        $this->storeAndProductDimensionReindexer = $storeAndProductDimensionReindexer;
        $this->indexerRegistry = $indexerRegistry;
        $this->dealerPrice = $dealerPrice;
    }

    /**
     * Responsible for processing a message in the 'magento.tier-price.handler' topic
     *
     * @param $tierData
     * @throws LocalizedException
     */
    public function process($tierData): void
    {
        $this->logger->info('TierPriceSave - entered process(…)');
        $tierData = $this->serializer->unserialize($tierData);
        $websiteId = $tierData['website'];
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $storeName = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getName();
        $this->logger->info('TierPriceSave for product type ' . $tierData["tier_price_product_type"] . '- tier price set ' . $tierData["tier_price_set"] . ' and store ' . $storeName);

        // Prepare for the loop
        $page = 0;
        $pageSize = $this->configuration->getBatchCount();
        $regularPriceAttrId = $this->dealerPrice->getRegularPriceAttributeId();
        $costAttrId = $this->dealerPrice->getCostAttributeId();

        $productCollection = $this->productCollectionFactory->create();
        $productCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(['sku', 'row_id']);
        $productCollection->addAttributeToFilter(
            'tier_price_set',
            $tierData['tier_price_set'],
            'eq'
        );
        $productCollection->addAttributeToFilter(
            'attribute_set_id',
            $tierData['tier_price_product_type'],
            'eq'
        );
        $productCollection->addStoreFilter($this->storeManager->getWebsite($tierData['website'])->getDefaultStore());

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
        $statement->where(
            'cst.attribute_id = ?',
            $costAttrId
        );
        $statement->where(
            'reg.attribute_id = ?',
            $regularPriceAttrId
        );
        $offSet = $page * $pageSize;
        $statement->limit($pageSize, $offSet);
        $connection = $productCollection->getResource()->getConnection();
        $productData = $connection->fetchAssoc($statement);
        $this->logger->info(__('Page size: %1', $pageSize));
        $this->logger->info(__('product count #%1', count($productData)));

        $tierPriceRangeData = [];
        $tierPriceRangeData = $this->getTierPriceMediaSetData($tierData);

        // The initial $productData has already been loaded
        do {
            $this->logger->info(__('Starting batch %1', $page));
            $productIds = $batchProducts = [];
            // Iterate over products, setting special prices as needed
            foreach ($productData as $product) {
                try {
                    $specialPrices = $this->getSpecialPrice($tierPriceRangeData, $product, $websiteId);
                    if (count($specialPrices) > 0) {
                        $batchProducts[] = $specialPrices;
                        $productIds[] = $product['row_id'];
                    }
                } catch (\Exception $e) {
                    $this->logger->error(
                        __('TierPriceSave failed for Product ID: %1', $product['row_id']),
                        $e->getTrace()
                    );
                }
            }           

            if (count($batchProducts) > 0) {
                $this->dealerPrice->setPricesPerStore($batchProducts);
            }
            
            // Run the catalogsearch_fulltext indexer for the store's products if the indexer is set to save
            if (!$this->isCatalogSearchFullTextIndexerScheduled()) {
                // @see Magento\CatalogSearch\Model\Indexer\Fulltext::processBatch(…)
                $this->logger->info(__('Starting batch reindex #%1', $page));
                $this->storeAndProductDimensionReindexer->execute($storeId, $productIds);
                $this->logger->info(__('Completed batch reindex #%1', $page));
            }

            // load next page product collection
            ++$page;
            
            $nextOffSet = $page * $pageSize;
            $statement->limit($pageSize, $nextOffSet);
            $productData = $connection->fetchAssoc($statement);           
            $this->logger->info(__('product count #%1', count($productData)));            
        } while (count($productData) > 0);

        $this->logger->info('TierPriceSave - exiting process(…)');
    }

    /**
     * Get Dealer Price based on price rules
     * 
     * @param array $tierPriceRangeData
     * @param $product
     * @param int $websiteId
     * @return array
     * @throws LocalizedException
     */
    protected function getSpecialPrice($tierPriceRangeData, $product, $websiteId)
    {
        $pricePerWebsite = [];
        try {
            $originalPrice = $product['price'];
            $tierData = $this->getMediaData($tierPriceRangeData, $originalPrice);
            $pricePerWebsite = $this->dealerPrice->getDealerDiscountedPrice($product, $websiteId, $tierData);
        } catch (\Exception $e) {
            $this->logger->error('Error in getting special price for sku ' . $product['sku'] . ' --store -- ' . $websiteId . ' -- ' . $e);
        }
        return $pricePerWebsite;
    }

    /**
     * Get Media Set price ranges by media set id
     *
     * @param array $tierData
     * @return array
     */
    public function getTierPriceMediaSetData($tierData): array
    {
        $tierPriceMediaData = [];
        try {
            $collection = $this->mediaFactory->create()->getCollection();
            $collection->getSelect()->join(
                ['tp' => 'tier_price'],
                'main_table.entity_id = tp.entity_id'
            )->where('main_table.entity_id = (?)', $tierData['entity_id']);
            $tierPriceData = $collection->getData();

            foreach ($tierPriceData as $data) {
                $tierPriceMediaData[] = $data;
            }
        } catch (\Exception $e) {
            $this->logger->error('error in getting tierprice media set data ' . $e);
        }

        return $tierPriceMediaData;
    }

    /**
     * Get Media Set data by selected media set, product type, websites and price
     *
     * @param array $tierPriceRangeData
     * @param float $originalprice
     * @return array
     */
    public function getMediaData($tierPriceRangeData, $originalprice): array
    {
        $tierPriceMediaData = [];
        foreach ($tierPriceRangeData as $tierPriceRange) {
            if ($tierPriceRange["from_price"] <= $originalprice && $tierPriceRange["to_price"] >= $originalprice) {
                $tierPriceMediaData[$tierPriceRange['website']]['percentage'] = $tierPriceRange['percentage'];
                $tierPriceMediaData[$tierPriceRange['website']]['adjustment_type'] = $tierPriceRange['adjustment_type'];
            }
        }
        return $tierPriceMediaData;
    }

    /**
     * Convenient retrieval of indexer state
     *
     * @return bool
     */
    private function isCatalogSearchFullTextIndexerScheduled(): bool
    {
        return $this->indexerRegistry->get('catalogsearch_fulltext')->isScheduled();
    }
}
