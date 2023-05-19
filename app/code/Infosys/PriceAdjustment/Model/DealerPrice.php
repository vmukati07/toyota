<?php

/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PriceAdjustment\Model;

use Infosys\PriceAdjustment\Logger\PriceCalculationLogger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Infosys\PriceAdjustment\Model\MediaFactory;
use Magento\Catalog\Model\ResourceModel\Product\Price\SpecialPrice;
use Magento\Catalog\Api\Data\SpecialPriceInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Infosys\PriceAdjustment\Model\Config\Configuration;
/**
 * Common functions for Dealer Price update
 */
class DealerPrice
{
    /**
     * Price storage table.
     *
     * @var string
     */
    private $priceTable = 'catalog_product_entity_decimal';

    /**
     * Datetime storage table.
     *
     * @var string
     */
    private $datetimeTable = 'catalog_product_entity_datetime';

    /** 
     * @var PriceCalculationLogger
     */
    private PriceCalculationLogger $logger;

    /**
     *  @var StoreManagerInterface 
     */
    private StoreManagerInterface $storeManager;

    /**
     *  @var MediaFactory
     */
    protected MediaFactory $mediaFactory;

    /**
     *  @var SpecialPrice
     */
    private SpecialPrice $specialPrice;

    /** 
     * @var SpecialPriceInterfaceFactory 
     */
    protected SpecialPriceInterfaceFactory $specialPriceFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Attribute
     */
    private Attribute $attributeResource;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $attributeRepository;
    
    /** 
     * @var Configuration 
     */
    private Configuration $configuration;

    /**
     * Special Price attribute ID.
     *
     * @var int
     */
    private $priceAttributeId;

     /**
     * Special price from attribute ID.
     *
     * @var int
     */
    private $priceFromAttributeId;

    /**
     * Special price to attribute ID.
     *
     * @var int
     */
    private $priceToAttributeId;

    /**
     * Items per operation.
     *
     * @var int
     */
    private $itemsPerOperation;


    /**
     * @param PriceCalculationLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param SpecialPrice $specialPrice
     * @param MediaFactory $mediaFactory
     * @param SpecialPriceInterfaceFactory $specialPriceFactory
     * @param Attribute $attributeResource
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param Configuration $configuration
     */
    public function __construct(
        PriceCalculationLogger $logger,
        StoreManagerInterface $storeManager,
        SpecialPrice $specialPrice,
        MediaFactory $mediaFactory,
        SpecialPriceInterfaceFactory $specialPriceFactory,
        Attribute $attributeResource,
        ProductAttributeRepositoryInterface $attributeRepository,
        Configuration $configuration
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->specialPrice = $specialPrice;
        $this->mediaFactory = $mediaFactory;
        $this->specialPriceFactory = $specialPriceFactory;
        $this->attributeResource = $attributeResource;
        $this->attributeRepository = $attributeRepository;
        $this->configuration = $configuration;
        $this->itemsPerOperation = $this->configuration->getSpecialPriceUpdateBatch();
    }

    /**
     *
     * @param $product
     * @param $websites
     * @return array
     * @throws LocalizedException
     */
    public function getSpecialPrice($product, $websites)
    {
        $pricePerWebsite = [];
        try {
            $originalPrice =  $product['price'];
            $tierPriceSet = $product['tier_price_set'];
            $productType = $product['attribute_set_id'];
            if (!empty($tierPriceSet)) {
                $tierData = $this->getMediaData($originalPrice, $websites, $tierPriceSet, $productType);
                foreach ($websites as $websiteId) {
                    $discountedPrice = $this->getDealerDiscountedPrice($product, $websiteId, $tierData);
                    if (count($discountedPrice) > 0) {
                        $pricePerWebsite[] = $discountedPrice;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in getting special price for sku ' . $product['sku'] . ' --website -- ' . $websiteId . ' -- ' . $e);
        }
        return $pricePerWebsite;
    }

    /**
     * Get Media Set data by msrp, websites, tier price set and product attribute set
     *
     * @param float $originalPrice
     * @param array $websites
     * @param string $tierPriceSet
     * @param string $productType
     * @return array
     */
    public function getMediaData($originalPrice, $websites, $tierPriceSet, $productType): array
    {
        $tierPriceMediaData = [];
        $collection = $this->mediaFactory->create()->getCollection();
        $collection->getSelect()->join(
            ['tp' => 'tier_price'],
            'main_table.entity_id = tp.entity_id'
        )
            ->where('main_table.media_set_selector = ?', $tierPriceSet)
            ->where('main_table.tier_price_product_type = ?', $productType)
            ->where('main_table.website IN (?)', $websites)
            ->where('tp.from_price <= ?', (float)$originalPrice)
            ->where('tp.to_price >= ?', (float)$originalPrice);
        $mediaData = $collection->getData();

        foreach ($mediaData as $data) {
            $tierPriceMediaData[$data['website']]['percentage'] = $data['percentage'];
            $tierPriceMediaData[$data['website']]['adjustment_type'] = $data['adjustment_type'];
        }
        return $tierPriceMediaData;
    }

    /**
     * Get Dealer Discounted Price
     * 
     * @param $product
     * @param int $websiteId
     * @param array $tierData
     * @return array
     * @throws LocalizedException
     */
    public function getDealerDiscountedPrice($product, $websiteId, $tierData): array
    {
        $pricePerWebsite = [];
        $originalPrice = $product['price'];
        $cost = $product['cost'];
        $store = $this->storeManager->getWebsite($websiteId)->getDefaultStore();
        if (!empty($store)) {
            if (!empty($tierData) && array_key_exists($websiteId, $tierData)) {
                $dealerPrice =  $originalPrice;
                if ($tierData[$websiteId]['adjustment_type'] == 1 && !empty($cost)) {
                    $newPrice = $cost + (($cost * $tierData[$websiteId]['percentage']) / 100);
                    if ($newPrice < $originalPrice) {
                        $dealerPrice =  $newPrice;
                    }
                } else {
                    $dealerPrice = ($originalPrice - (($originalPrice * $tierData[$websiteId]['percentage']) / 100));
                }

                //TODO need check with AEM to avoid inserting special price if special price is same as MSRP 
                if ($dealerPrice < $originalPrice) {
                    $pricePerWebsite = [
                        "price" => $dealerPrice,
                        "store_id" => $store->getId(),
                        "row_id" => $product['row_id'],                       
                        "type" => 'update'
                    ];
                } else {
                    $pricePerWebsite = [
                        "store_id" => $store->getId(),
                        "row_id" => $product['row_id'],
                        "sku" => $product['sku'],
                        "type" => 'delete'
                    ];
                }
            } else {
                $pricePerWebsite = [                 
                    "store_id" => $store->getId(),
                    "row_id" => $product['row_id'],
                    "sku" => $product['sku'],
                    "type" => 'delete'
                ];
            }
        }
        return $pricePerWebsite;
    }

    /**
     * Set special price for products.
     *
     * @param array $specialPrices
     * @return bool
     * @throws \Exception
     */
    public function setPricesPerStore($specialPrices)
    {
        try {
            $updateSpecialPrice = $deleteSpecialPrice = [];            
            foreach ($specialPrices as $price) {              
                if($price['type'] == "update") {
                    $updateSpecialPrice[] = $price;
                } else {
                    $deleteSpecialPrice[] = $price;                   
                }
            }
            
            if(count($updateSpecialPrice) > 0) {
                $this->updateDealerPrice($updateSpecialPrice);
            }
            
            if(count($deleteSpecialPrice) > 0) {
                $this->deletePricesPerStore($deleteSpecialPrice);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Special price update error ' . $e);
        }
        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function updateDealerPrice(array $prices)
    {
        $formattedPrices = [];

        /** @var \Magento\Catalog\Api\Data\SpecialPriceInterface $price */
        foreach ($prices as $price) {
                $formattedPrices[] = [
                    'store_id' => $price['store_id'],
                    $this->specialPrice->getEntityLinkField() => $price['row_id'],
                    'value' => $price['price'],
                    'attribute_id' => $this->getSpecialPriceAttributeId(),
                ];  
        }
        $connection = $this->attributeResource->getConnection();
        $connection->beginTransaction();

        try {
            $this->updateItemsPrice($formattedPrices, $this->priceTable);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Could not save Prices.'),
                $e
            );
        }

        return true;
    }

    /**
     * Update items in database.
     *
     * @param array $items
     * @param string $table
     * @return void
     */
    private function updateItemsPrice(array $items, $table)
    {
        foreach (array_chunk($items, $this->itemsPerOperation) as $itemsBunch) {
            $this->attributeResource->getConnection()->insertOnDuplicate(
                $this->attributeResource->getTable($table),
                $itemsBunch,
                ['value']
            );
        }
    }


    /**
     * Delete special price by store Id.
     *
     * @param array $deleteSpecialPrice
     * @return bool
     * @throws \Exception
     */
    public function deletePricesPerStore(array $deleteSpecialPrice)
    {
        try {
            $storeIds = array_unique(array_map(function ($storeId) {
                return $storeId['store_id'];
            }, $deleteSpecialPrice)); 

            $rowIds = array_unique(array_map(function ($rowId) {
                return $rowId['row_id'];
            }, $deleteSpecialPrice));
            
            $priceTable = $this->attributeResource->getTable($this->priceTable);
            $deteTimeTable = $this->attributeResource->getTable($this->datetimeTable);
            $connection = $this->attributeResource->getConnection();
            $connection->beginTransaction();
        
            foreach (array_chunk($rowIds, $this->itemsPerOperation) as $idsBunch) {
                $this->attributeResource->getConnection()->delete(
                    $priceTable,
                    [
                        'attribute_id = ?' => $this->getSpecialPriceAttributeId(),
                        $this->specialPrice->getEntityLinkField() . ' IN (?)' => $idsBunch,
                        'store_id IN (?)' => $storeIds
                    ]
                );
            }
            foreach (array_chunk($rowIds, $this->itemsPerOperation) as $idsBunch) {
                $this->attributeResource->getConnection()->delete(
                    $deteTimeTable,
                    [
                        'attribute_id IN (?)' => [$this->getPriceFromAttributeId(), $this->getPriceToAttributeId()],
                        $this->specialPrice->getEntityLinkField() . ' IN (?)' => $idsBunch,
                        'store_id IN (?)' => $storeIds
                    ]
                );
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $this->logger->error('Special price delete error ' . $e);
        }
        return true;
    }

    
    /**
     * Get special price attribute ID.
     *
     * @return int
     */
    private function getSpecialPriceAttributeId()
    {
        if (!$this->priceAttributeId) {
            $this->priceAttributeId = $this->attributeRepository->get('special_price')->getAttributeId();
        }
        return $this->priceAttributeId;
    }

    /**
     * Get product regular price attribute ID.
     *
     * @return int
     */
    public function getRegularPriceAttributeId()
    {
        return $this->attributeRepository->get('price')->getAttributeId();
    }

    /**
     * Get product Cost attribute ID.
     *
     * @return int
     */
    public function getCostAttributeId()
    {
        return $this->attributeRepository->get('cost')->getAttributeId();
    }

    /**
     * Get tier price set attribute ID.
     *
     * @return int
     */
    public function getTierPriceSetAttrId()
    {
        return $this->attributeRepository->get('tier_price_set')->getAttributeId();
    }

    /**
     * Get special price from attribute ID.
     *
     * @return int
     */
    private function getPriceFromAttributeId()
    {
        if (!$this->priceFromAttributeId) {
            $this->priceFromAttributeId = $this->attributeRepository->get('special_from_date')->getAttributeId();
        }
        return $this->priceFromAttributeId;
    }

    /**
     * Get special price to attribute ID.
     *
     * @return int
     */
    private function getPriceToAttributeId()
    {
        if (!$this->priceToAttributeId) {
            $this->priceToAttributeId = $this->attributeRepository->get('special_to_date')->getAttributeId();
        }
        return $this->priceToAttributeId;
    }

}
