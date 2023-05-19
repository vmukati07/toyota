<?php

/**
 * @package Infosys/XtentoProductExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright � 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Model\Export\Entity;

use Magento\Framework\Exception\LocalizedException;
use Infosys\XtentoProductExport\Logger\ProductExportLogger;
use Infosys\XtentoProductExport\Helper\Data;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Extented for Product export optimization
 */
class Product extends \Xtento\ProductExport\Model\Export\Entity\AbstractEntity
{
    protected $entityType = \Xtento\ProductExport\Model\Export::ENTITY_PRODUCT;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    private ProductExportLogger $loggerManager;

    private Data $helperData;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * Product constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Xtento\ProductExport\Model\ProfileFactory $profileFactory
     * @param \Xtento\ProductExport\Model\ResourceModel\History\CollectionFactory $historyCollectionFactory
     * @param \Xtento\ProductExport\Model\Export\Data $exportData
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param ProductExportLogger $loggerManager
     * @param Data $helperData
     * TimezoneInterface $localeDate
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Xtento\ProductExport\Model\ProfileFactory $profileFactory,
        \Xtento\ProductExport\Model\ResourceModel\History\CollectionFactory $historyCollectionFactory,
        \Xtento\ProductExport\Model\Export\Data $exportData,
        \Magento\Store\Model\StoreFactory $storeFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        ProductExportLogger $loggerManager,
        Data $helperData,
        TimezoneInterface $localeDate,
        array $data = []
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->stockHelper = $stockHelper;
        $this->logger = $loggerManager;
        $this->helperData = $helperData;
        $this->localeDate = $localeDate;
        parent::__construct($context, $registry, $profileFactory, $historyCollectionFactory, $exportData, $storeFactory, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $collection = $this->productCollectionFactory->create();
        //    ->addAttributeToSelect('*');
        $collection->addTaxPercents();

        $this->collection = $collection;
        parent::_construct();
    }

    public function runExport($forcedCollectionItem = false)
    {
        $previousStoreId = false;
        if ($this->getProfile()) {
            if ($this->getProfile()->getStoreId()) {
                $profileStoreId = $this->getProfile()->getStoreId();
                if ($this->storeManager->getStore()->getId() != $profileStoreId) {
                    $previousStoreId = $this->storeManager->getStore()->getId();
                    $this->storeManager->setCurrentStore($profileStoreId); // fixes catalog price rules
                }
                $store = $this->storeManager->getStore($profileStoreId);
                if ($store->getId()) {
                    $websiteId = $store->getWebsiteId();
                } else {
                    throw new LocalizedException(__('Product export failed. The specified store_id %1 does not exist anymore. Please update the profile in the Stores & Filters tab and select a valid store view.', $profileStoreId));
                }
                $this->collection->getSelect()->joinLeft(
                    $this->resourceConnection->getTableName('catalog_product_index_price') . ' AS price_index',
                    'price_index.entity_id=e.entity_id AND customer_group_id=' . intval($this->getProfile()->getCustomerGroupId() ? $this->getProfile()->getCustomerGroupId() : 0) . ' AND price_index.website_id=' . $websiteId,
                    [
                        'min_price' => 'min_price',
                        'max_price' => 'max_price',
                        'tier_price' => 'tier_price',
                        'final_price' => 'final_price'
                    ]
                );
                $this->collection->addStoreFilter($profileStoreId);
                $this->collection->setStore($profileStoreId);
                $this->collection->/*setStore($profileStoreId)->addWebsiteFilter(Mage::app()->getStore($profileStoreId)->getWebsiteId())->*/addAttributeToSelect("tax_class_id");
            }
            /** Add product reviews */
            /*
            $this->collection->getSelect()->joinLeft(
                $this->resourceConnection->getTableName('review_entity_summary') . ' AS reviews',
                'reviews.entity_pk_value=e.entity_id AND customer_group_id=0 AND reviews.store_id=' . $store->getId(),
                [
                    'reviews_count' => 'reviews_count',
                    'rating_summary' => 'rating_summary'
                ]
            );
            */
            if ($this->getProfile()->getOutputType() == 'csv' || $this->getProfile()->getOutputType() == 'xml') {
                // Fetch all fields
                $this->collection->addAttributeToSelect('*');
            } else {
                $attributesToSelect = explode(",", $this->getProfile()->getAttributesToSelect());

                if (empty($attributesToSelect) || (isset($attributesToSelect[0]) && empty($attributesToSelect[0]))) {
                    $attributes = '*';
                } else {
                    // Get all attributes which should be always fetched
                    $attributes = ['entity_id', 'sku', 'price', 'name', 'status', 'url_key', 'type_id', 'image'];
                    $attributes = array_merge($attributes, $attributesToSelect);
                    $attributes = array_unique($attributes);
                }
                $this->collection->addAttributeToSelect($attributes);
            }
            #echo($this->collection->getSelect());

            if ($this->getProfile()->getExportFilterProductVisibility() != '') {
                $this->collection->addAttributeToFilter(
                    'visibility',
                    ['in' => explode(",", $this->getProfile()->getExportFilterProductVisibility())]
                );
            }
            if ($this->getProfile()->getExportFilterProductStatus() != '') {
                $this->collection->addAttributeToFilter(
                    'status',
                    ['in' => explode(",", $this->getProfile()->getExportFilterProductStatus())]
                );
            }
            if ($this->getProfile()->getExportFilterInstockOnly() === "1") {
                $this->stockHelper->addInStockFilterToCollection($this->collection);
            }

            //Filter by include_in_feeds attribute value if config set as yes
            if ($this->getProfile()->getExportFilterIncludeInFeed() === "1") {
                $this->collection->addAttributeToFilter('include_in_feeds', 1);
            }
			
			//Filter by image, which product don't have the image should not be in feed
			if($this->getProfile()->getExportFilterProductFeedImage() === "1") {
                $this->collection->addAttributeToFilter('image',array('notnull'=>'','neq'=>'no_selection'));
            }
        }

        $isExportEnabled = $this->helperData->getConfig('products_export_xtento/general/active');

        if ($isExportEnabled) {
            $result = $this->_runExport($forcedCollectionItem);
        } else {
            return parent::_runExport($forcedCollectionItem);
        }

        if ($previousStoreId !== false) {
            $this->storeManager->setCurrentStore($previousStoreId); // Reset store back to what it was before
        }
        return $result;
    }

    protected function _runExport($forcedCollectionItem = false)
    {
        $hiddenProductTypes = explode(",", $this->getProfile()->getExportFilterProductType());
        if (!empty($hiddenProductTypes)) {
            $this->collection->addAttributeToFilter('type_id', ['nin' => $hiddenProductTypes]);
        }

        $exportFields = [];
        // Get validation profile
        /* Alternative approach if conditions check fails, we've seen this happening in Magento 1 installations, the profile conditions were simply empty and the profile needed to be loaded again: */
        $validationProfile = $this->getProfile();
        $exportConditions = $validationProfile->getData('conditions_serialized');

        if (strlen($exportConditions) > 90) {
            // Force load profile for rule validation, as it fails on some stores if the profile is not re-loaded
            $validationProfile = $this->profileFactory->create()->load($this->getProfile()->getId());
        }
        // Reset export classes
        $this->exportDataSingleton->resetExportClasses();
        // Backup original rule_data
        $origRuleData = $this->_registry->registry('rule_data');
        $ruleDataChanged = false;
        // Register rule information for catalog rules
        $storeId = 0;
        if ($this->getProfile()->getStoreId()) {
            $storeId = $this->getProfile()->getStoreId();
        }
        $productStore = $this->storeFactory->create()->load($storeId);
        if ($productStore) {
            $this->_registry->unregister('rule_data');
            $this->_registry->register(
                'rule_data',
                new \Magento\Framework\DataObject(
                    [
                        'store_id' => $storeId,
                        'website_id' => $productStore->getWebsiteId(),
                        'customer_group_id' => $this->getProfile()->getCustomerGroupId() ?
                            $this->getProfile()->getCustomerGroupId() : 0, // 0 = NOT_LOGGED_IN
                    ]
                )
            );
            $ruleDataChanged = true;
        }
        // Dispatch event before export to add ability for users to manipulate the collection / add additional filters directly to the collection
        $this->_eventManager->dispatch(
            'xtento_productexport_export_before_prepare_collection',
            [
                'entity' => $this->getProfile()->getEntity(),
                'profile' => $this->getProfile(),
                'collection' => $this->collection,
                'forced_collection_item' => $forcedCollectionItem // Used with event exports only. Then there is just this item, and no collection itself. This would contain the item to export then
            ]
        );
        $collectionBatchSize = $this->exportDataSingleton->getCollectionBatchSize();
        $exportedIds = [];

        // Get export fields
        if ($forcedCollectionItem === false) {
            $collectionCount = null;
            $currItemNo = 1;
            $originalCollection = $this->collection;
            $originalCollection->setOrder('entity_id', 'asc'); // Fix for missing categories as supplied by Jan-Henning Rühl
            $originalCollection->getSelect()->joinLeft(
                ['stock' => 'cataloginventory_stock_item'],
                "stock.product_id = e.entity_id",
                ['stock.qty', 'stock.manage_stock']
            )->where('stock.stock_id= (?)', 1);
            $currPage = 1;
            $lastPage = 0;
            $break = false;
            $collectData = [];

            while ($break !== true) {
                $collection = clone $originalCollection;
                $collection->setPageSize($collectionBatchSize);
                $collection->setCurPage($currPage);
                $collection->load();

                if (is_null($collectionCount)) {
                    // Note: getSize() is cached sometimes. If there are issues that getLastPageNumber is wrong, implement own getLastPageNumber/getSize function using count($collection->getItems())
                    $collectionCount = $collection->getSize();
                    $lastPage = $collection->getLastPageNumber();
                }
                if ($currPage == $lastPage) {
                    $break = true;
                }
                $currPage++;

                foreach ($collection as $collectionItem) {
                    if ($this->getProfile()->getEntity() == \Xtento\ProductExport\Model\Export::ENTITY_REVIEW) {
                        // Bug in review collection, reviews twice sometimes. Register each ID and avoid duplicates.
                        if (isset($exportedIds[$collectionItem->getId()])) {
                            continue;
                        } else {
                            $exportedIds[$collectionItem->getId()] = 1;
                        }
                    }

                    $collectionItemValidated = true;
                    $this->_eventManager->dispatch('xtento_productexport_custom_validation', [
                        'validationProfile'             => $validationProfile,
                        'collectionItem'                => $collectionItem,
                        'collectionItemValidated'       => &$collectionItemValidated,
                    ]);

                    if ($this->getExportType() == \Xtento\ProductExport\Model\Export::EXPORT_TYPE_TEST || ($collectionItemValidated && $validationProfile->validate($collectionItem))) {
                        $returnData = $collectionItem->getData();
                        $returnData = $this->isSpecialPriceActive($returnData);
                        $returnData = $this->getXtentoMappedCategory($returnData, $storeId);
                        $returnData['product_url'] = $collectionItem->getProductUrl();
                        $returnData['stock'] = ['qty' => $returnData['qty'], 'manage_stock' => $returnData['manage_stock']];
                        $this->returnArray[] = $returnData;
                        $currItemNo++;
                    }
                }
            }
        } else {
            $rawFilters = $this->getRawCollectionFilters();
            $collectionItemValidated = true;
            // Manually check collection filters against collection item as there is no real collection
            if (is_array($rawFilters)) {
                foreach ($rawFilters as $filter) {
                    foreach ($filter as $filterField => $filterCondition) {
                        $filterField = str_replace("main_table.", "", $filterField);
                        $itemData = $forcedCollectionItem->getData($filterField);
                        foreach ($filterCondition as $filterConditionType => $acceptedValues) {
                            if ($filterConditionType == 'in') {
                                if (!in_array($itemData, $acceptedValues)) {
                                    $collectionItemValidated = false;
                                    break 3;
                                }
                            }
                            // Date filters not implemented (yet?)
                            #var_dump($filterField, $itemData, $acceptedValues);
                        }
                    }
                }
            }
            // "Export only new" filter: For collections, this is joined in the \Xtento\ProductExport\Model\Export model with the exported entity collection directly. This doesn't work for direct model exports. Thus, we need to add the filter here, too.
            if ($this->exportOnlyNewFilter) {
                $historyCollection = $this->historyCollectionFactory->create();
                $historyCollection->addFieldToFilter('entity_id', $forcedCollectionItem->getData('entity_id'));
                $historyCollection->addFieldToFilter('entity', $this->getProfile()->getEntity());
                $historyCollection->addFieldToFilter('profile_id', $this->getProfile()->getId());
                if ($historyCollection->getSize() > 0) {
                    $collectionItemValidated = false;
                }
            }
            #Zend_Debug::dump($forcedCollectionItem->getData());
            #var_dump($collectionItemValidated);
            #die();
            $this->_eventManager->dispatch('xtento_productexport_custom_validation', [
                'validationProfile'             => $validationProfile,
                'collectionItem'                => $forcedCollectionItem,
                'collectionItemValidated'       => &$collectionItemValidated,
            ]);
            // If all filters pass, then export the item
            if ($this->getExportType() == \Xtento\ProductExport\Model\Export::EXPORT_TYPE_TEST || ($collectionItemValidated && $validationProfile->validate($forcedCollectionItem))) {
                $returnData = $this->exportData(new \Xtento\ProductExport\Model\Export\Entity\Collection\Item($forcedCollectionItem, $this->entityType, 1, 1), $exportFields);
                if (!empty($returnData)) {
                    $returnData = $this->isSpecialPriceActive($returnData);
                    $returnData = $this->getXtentoMappedCategory($returnData, $storeId);
                    $this->returnArray[] = $returnData;
                }
            }
        }
        if ($ruleDataChanged) {
            $this->_registry->unregister('rule_data');
            $this->_registry->register('rule_data', $origRuleData);
        }
        #var_dump(__FILE__, $this->returnArray); die();
        return $this->returnArray;
    }

    /**
     * Return Special price is active or not
     *
     * @param array $returnData
     * @return array
     */
    protected function isSpecialPriceActive($returnData): array
    {
        $dateToday = $this->localeDate->date();
        $dateToday->setTime(0, 0, 0);
        $isSpecialPriceActive = true;
        if (isset($returnData['special_from_date'])) {
            $fromDate = $this->localeDate->date(new \DateTime($returnData['special_from_date']));
            $fromDate->setTime(0, 0, 0);
            if ($dateToday < $fromDate) {
                $isSpecialPriceActive = false;
            }
        }
        if (isset($returnData['special_to_date'])) {
            $toDate = $this->localeDate->date(new \DateTime($returnData['special_to_date']));
            $toDate->setTime(0, 0, 0);
            if ($dateToday > $toDate) {
                $isSpecialPriceActive = false;
            }
        }
        if ($isSpecialPriceActive) {
            $returnData['special_price_active'] = 1;
        } else {
            $returnData['special_price_active'] = 0;
        }
        return $returnData;
    }

    public function setCollectionFilters($filters)
    {
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                foreach ($filter as $attribute => $filterArray) {
                    $this->collection->addAttributeToFilter($attribute, $filterArray);
                }
            }
        }
        $this->setRawCollectionFilters($filters);
        return $this->collection;
    }

    public function addExportOnlyNewFilter()
    {
        $this->exportOnlyNewFilter = true;
    }

    protected function exportData($collectionItem, $exportFields)
    {
        return $this->exportDataSingleton
            ->setShowEmptyFields($this->getShowEmptyFields())
            ->setProfile($this->getProfile() ? $this->getProfile() : new \Magento\Framework\DataObject)
            ->setExportFields($exportFields)
            ->getExportData($this->entityType, $collectionItem);
    }

    /**
     * Get Xtento Mapped Category from export profile
     *
     * @param array $returnData, $storeId
     * @return array
     */
    public function getXtentoMappedCategory($returnData, $storeId)
    {
        $product = $this->productRepository->getById((int) $returnData['entity_id'], false, $storeId ? $storeId : null);
        $categoryIds = $product->getCategoryIds();
        $mappedCategory = '';
        $categoryMapping = json_decode($this->getProfile()->getCategoryMapping(), true) ?: [];
        $longestPathCount = 0;
        foreach ($categoryIds as $categoryId) {
            if (isset($categoryMapping[$categoryId]) && !empty($categoryMapping[$categoryId])) {
                $taxonomyPath = $categoryMapping[$categoryId];
                if (stristr($taxonomyPath, '>') === false) {
                    $mappedCategory = $taxonomyPath;
                    break;
                }
                $pathLevel = substr_count($taxonomyPath, '>');
                if ($pathLevel > $longestPathCount) {
                    // We want the deepest/longest taxonomy category mapped, so with most > as possible
                    $longestPathCount = $pathLevel;
                    $mappedCategory = $taxonomyPath;
                }
            }
        }
        if(!empty($mappedCategory)) {
            $returnData['xtento_mapped_category'] = $mappedCategory;
        }
        return $returnData;
    }
}
