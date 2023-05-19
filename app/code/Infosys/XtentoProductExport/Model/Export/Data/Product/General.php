<?php

/**
 * @package     Infosys/XtentoProductExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Model\Export\Data\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Xtento\ProductExport\Model\Export\Data\Product\General as CoreGeneral;

class General extends CoreGeneral
{
    /**
     * @var array
     */
    protected static $attributeSetCache = [];

    /**
     * @var boolean
     */
    protected static $mediaGalleryBackend = false;

    /**
     * @var array
     */
    protected static $categoryMapping = null;

    /**
     * @var \Magento\Tax\Model\Config
     */
    protected $taxConfig;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $resourceProduct;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    protected $attributeSetFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * General constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Xtento\XtCore\Helper\Date $dateHelper
     * @param \Xtento\XtCore\Helper\Utils $utilsHelper
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product $resourceProduct
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Tax\Model\Calculation $taxCalculation
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Xtento\XtCore\Helper\Date $dateHelper,
        \Xtento\XtCore\Helper\Utils $utilsHelper,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Catalog\Model\ResourceModel\Product $resourceProduct,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        ProductRepositoryInterface $productRepository,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $dateHelper,
            $utilsHelper,
            $taxConfig,
            $resourceProduct,
            $storeManager,
            $attributeSetFactory,
            $localeDate,
            $productRepository,
            $taxCalculation,
            $productMetadata,
            $objectManager,
            $imageHelper,
            $catalogHelper,
            $resource,
            $resourceCollection,
            $data
        );
        $this->taxConfig = $taxConfig;
        $this->resourceProduct = $resourceProduct;
        $this->storeManager = $storeManager;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->localeDate = $localeDate;
        $this->productRepository = $productRepository;
        $this->taxCalculation = $taxCalculation;
        $this->objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
        $this->imageHelper = $imageHelper;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * ExportProductData Method
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param array $returnArray
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function exportProductData($product, &$returnArray)
    {
        // Check if pub folder is being used
        $usesPubFolder = false;
        if ($this->getProfile()->getRemovePubFolderFromUrls()) {
            $usesPubFolder = true;
        } else {
            $directoryList = $this->objectManager->get(\Magento\Framework\App\Filesystem\DirectoryList::class);
            if ($directoryList->getUrlPath('pub') == '') {
                $usesPubFolder = true;
            }
        }

        // Set store
        if ($this->getStoreId()) {
            $product->setStoreId($this->getStoreId());
            $this->writeValue('store_id', $this->getStoreId());
        } else {
            $this->writeValue('store_id', 0);
        }

        $exportAllFields = false;
        if ($this->getProfile()->getOutputType() == 'xml') {
            $exportAllFields = true;
        }

        #\Zend_Debug::dump($product->getData()); die();
        foreach ($product->getData() as $key => $value) {
            if ($key == 'entity_id') {
                continue;
            }
            if ($key == 'price') {
                $this->writeValue('original_price', $value);
                continue;
            }
            if (!$this->fieldLoadingRequired($key)) {
                if ($this->fieldLoadingRequired($key . '_raw') && !$exportAllFields) {
                    $this->writeValue($key . '_raw', $value);
                }
                continue;
            }
            if ($key == 'cost') {
                $this->writeValue(
                    'cost',
                    $this->resourceProduct->getAttributeRawValue(
                        $product->getId(),
                        'cost',
                        $this->getStoreId()
                    )
                );
                continue;
            }
            if ($key == 'min_price' || $key == 'max_price' || $key == 'special_price') {
                $value = $this->addTax($product, $value, $key);
            }
            if ($key == 'qty') {
                $value = sprintf('%d', $value);
            }
            if ($key == 'image' || $key == 'small_image' || $key == 'thumbnail') {
                $this->writeValue($key . '_raw', $value);
                $imageUrl = $this->storeManager->getStore($this->getStoreId())
                        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/' . ltrim(
                            (string)$value,
                            '/'
                        );
                if ($usesPubFolder) {
                    // Remove /pub/ from URL
                    $imageUrl = str_replace('/pub/', '/', $imageUrl);
                }
                $this->writeValue($key, $imageUrl);
                if ($this->fieldLoadingRequired($key . '_cache_url') && !empty($value)) {
                    $cacheUrl = $this->imageHelper->init($product, $key)->setImageFile($value)->getUrl();
                    if ($usesPubFolder) {
                        // Remove /pub/ from URL
                        $cacheUrl = str_replace('/pub/', '/', $cacheUrl);
                    }
                    $this->writeValue($key . '_cache_url', $cacheUrl);
                }
                continue;
            }
            $attribute = $product->getResource()->getAttribute($key);
            if ($attribute instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute) {
                $attribute->setStoreId($product->getStoreId());
            }
            
            $attrText = '';
            if ($attribute) {
                if ($attribute->getFrontendInput() === 'media_image') {
                    $this->writeValue($key . '_raw', $value);
                    $imageLink = $this->storeManager->getStore($this->getStoreId())
                            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/' . ltrim(
                                (string)$value,
                                '/'
                            );
                    if ($usesPubFolder) {
                        // Remove /pub/ from URL
                        $imageLink = str_replace('/pub/', '/', $imageLink);
                    }
                    $this->writeValue($key, $imageLink);
                    continue;
                }
                if ($attribute->getFrontendInput() === 'weee' || $attribute->getFrontendInput() === 'media_gallery') {
                    // Don't export certain frontend_input values
                    continue;
                }
                if ($attribute->usesSource()) {
                    try {
                        $attrText = $product->getAttributeText($key);
                    } catch (\Exception $e) {
                        //echo "Problem with attribute $key: ".$e->getMessage();
                        continue;
                    }
                }
            }
            if (!empty($attrText)) {
                if (is_array($attrText)) {
                    // Multiselect:
                    foreach ($attrText as $index => $val) {
                        if (!is_array($index) && !is_array($val)) {
                            $this->writeValue($key . '_value_' . $index, $val);
                        }
                    }
                    $this->writeValue($key, implode(",", $attrText));
                } else {
                    if ($attribute->getFrontendInput() == 'multiselect') {
                        $this->writeValue($key . '_value_0', $attrText);
                    }
                    $this->writeValue($key, $attrText);
                }
            } else {
                $this->writeValue($key, $value);
            }
            if ($key == 'visibility'
                || $key == 'status'
                || $key == 'tax_class_id'
                || ($this->fieldLoadingRequired($key.'_raw')
                && !$exportAllFields)
            ) {
                $this->writeValue($key . '_raw', $value);
            }
        }

        // Extended fields
        if ($this->fieldLoadingRequired('xtento_mapped_category')) {
            if (self::$categoryMapping === null) {
                self::$categoryMapping = json_decode($this->getProfile()->getCategoryMapping(), true) ?: [];
            }
            $mappedCategory = '';
            $categoryIds = $product->getCategoryIds();
            $longestPathCount = 0;
            foreach ($categoryIds as $categoryId) {
                if (isset(self::$categoryMapping[$categoryId]) && !empty(self::$categoryMapping[$categoryId])) {
                    $taxonomyPath = self::$categoryMapping[$categoryId];
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
            if (empty($mappedCategory)) {
                // Fall back to default attribute
                $mappedCategory = $product->getData('google_product_category');
            }
            $this->writeValue('xtento_mapped_category', $mappedCategory);
        }
        if ($this->fieldLoadingRequired('product_url')) {
            $productUrl = $product->getProductUrl(false);
            if ($this->getProfile()->getExportUrlRemoveStore()) {
                $productUrl = strtok($productUrl, '?');
            }
            $this->writeValue('product_url', $productUrl);
        }
        if ($this->fieldLoadingRequired('price')) {
            $price = $this->getPrice($product);
            $this->writeValue('price', $price);
            if ($this->fieldLoadingRequired('price_incl_tax')) {
                $this->writeValue('price_incl_tax', $this->getPriceInclTax($product, $price));
            }
        }
        if ($this->fieldLoadingRequired('final_price')) {
            $this->writeValue('final_price', $product->getPriceInfo()->getPrice('final_price')->getValue());
        }
        if ($this->fieldLoadingRequired('catalogrule_price')) {
            // Unfortunately the code in CatalogRule\Pricing\Price\CatalogPriceRule ignores the current scope :(
            $catalogRulePrice = $this->objectManager->get(\Magento\CatalogRule\Model\ResourceModel\Rule::class)
                ->getRulePrice(
                    $this->objectManager->get(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
                        ->scopeDate($this->getStoreId()),
                    $this->storeManager->getStore($this->getStoreId())->getWebsiteId(),
                    $this->getProfile()->getCustomerGroupId() ?: 0,
                    $product->getId()
                );
            $catalogRulePrice = $catalogRulePrice ? floatval($catalogRulePrice) : null;
            $this->writeValue('catalogrule_price', $catalogRulePrice);
            //if ($catalogRulePrice) {
            //    $catalogRulePrice = $this->priceCurrency->convertAndRound($catalogRulePrice, $this->getStoreId());
            //}
        }
        if ($this->fieldLoadingRequired('attribute_set_name')) {
            $attributeSetId = $product->getAttributeSetId();
            if (!array_key_exists($attributeSetId, self::$attributeSetCache)) {
                $attributeSet = $this->attributeSetFactory->create()->load($attributeSetId);
                $attributeSetName = '';
                if ($attributeSet->getId()) {
                    $attributeSetName = $attributeSet->getAttributeSetName();
                    $this->writeValue('attribute_set_name', $attributeSetName);
                }
                self::$attributeSetCache[$attributeSetId] = $attributeSetName;
            } else {
                $this->writeValue('attribute_set_name', self::$attributeSetCache[$attributeSetId]);
            }
        }

        // Upsell product IDs / SKUs
        if ($this->fieldLoadingRequired('upsell_product_ids') && !$exportAllFields) {
            $this->writeValue('upsell_product_ids', implode(",", $product->getUpSellProductIds()));
        }
        if ($this->fieldLoadingRequired('upsell_product_skus') && !$exportAllFields) {
            $skus = [];
            foreach ($product->getUpSellProductCollection() as $upsellProduct) {
                $skus[] = $upsellProduct->getSku();
            }
            $this->writeValue('upsell_product_skus', implode(",", $skus));
        }
        // Cross-Sell product IDs / SKUs
        if ($this->fieldLoadingRequired('cross_sell_product_ids') && !$exportAllFields) {
            $this->writeValue('cross_sell_product_ids', implode(",", $product->getCrossSellProductIds()));
        }
        if ($this->fieldLoadingRequired('cross_sell_product_skus') && !$exportAllFields) {
            $skus = [];
            foreach ($product->getCrossSellProductCollection() as $crosssellProduct) {
                $skus[] = $crosssellProduct->getSku();
            }
            $this->writeValue('cross_sell_product_skus', implode(",", $skus));
        }
        // Related product IDs / SKUs
        if ($this->fieldLoadingRequired('related_product_ids') && !$exportAllFields) {
            $this->writeValue('related_product_ids', implode(",", $product->getRelatedProductIds()));
        }
        if ($this->fieldLoadingRequired('related_product_skus') && !$exportAllFields) {
            $skus = [];
            foreach ($product->getRelatedProductCollection() as $relatedProduct) {
                $skus[] = $relatedProduct->getSku();
            }
            $this->writeValue('related_product_skus', implode(",", $skus));
        }
        if ($this->fieldLoadingRequired('website_codes') && !$exportAllFields) {
            $websiteCodes = [];
            foreach ($product->getWebsiteIds() as $websiteId) {
                $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
                $websiteCodes[$websiteCode] = $websiteCode;
            }
            $this->writeValue('website_codes', join(',', $websiteCodes));
        }
        // Is special price active?
        if ($this->fieldLoadingRequired('special_price_active') && !$exportAllFields) {
            $dateToday = $this->localeDate->date();
            $dateToday->setTime(0, 0, 0);
            $isSpecialPriceActive = true;
            if ($product->getSpecialFromDate()) {
                $fromDate = $this->localeDate->date(new \DateTime($product->getSpecialFromDate()));
                $fromDate->setTime(0, 0, 0);
                if ($dateToday < $fromDate) {
                    $isSpecialPriceActive = false;
                }
            } else {
                $isSpecialPriceActive = false;
            }
            if ($product->getSpecialToDate()) {
                $toDate = $this->localeDate->date(new \DateTime($product->getSpecialToDate()));
                $toDate->setTime(0, 0, 0);
                if ($dateToday > $toDate) {
                    $isSpecialPriceActive = false;
                }
            }
            $this->writeValue('special_price_active', (int)$isSpecialPriceActive);
        }

        if ($this->fieldLoadingRequired('images') && !$exportAllFields) {
            $returnArray['images'] = [];
            $originalWriteArray = & $this->writeArray;
            $this->writeArray = & $returnArray['images'];
            if (version_compare($this->productMetadata->getVersion(), '2.1', '<')) {
                // Magento 2.0
                if (self::$mediaGalleryBackend === false) {
                    $attributes = $product->getTypeInstance()->getSetAttributes($product);
                    if (isset($attributes['media_gallery'])) {
                        self::$mediaGalleryBackend = $attributes['media_gallery']->getBackend();
                    }
                }
                if (self::$mediaGalleryBackend !== false) {
                    self::$mediaGalleryBackend->afterLoad($product);
                    $mediaGalleryImages = $product->getMediaGalleryImages();
                    if (is_array($mediaGalleryImages)) {
                        foreach ($mediaGalleryImages as $mediaGalleryImage) {
                            $this->writeArray = &$returnArray['images'][];
                            foreach ($mediaGalleryImage->getData() as $key => $value) {
                                $this->writeValue($key, $value);
                            }
                        }
                    }
                }
            } else {
                // Magento 2.1+, no DI possible as it does not exist for Magento 2.0
                /** @var \Magento\Catalog\Model\Product\Gallery\ReadHandler $galleryReadHandler */
                $galleryReadHandler = $this->objectManager->get(
                    \Magento\Catalog\Model\Product\Gallery\ReadHandler::class
                );
                $galleryReadHandler->execute($product);
                $mediaGalleryImages = $product->getMediaGalleryImages();
                // ReadHandler only loads disabled=0 images, meaning,
                //hidden images are not exported. To fix this, you must load the full product like this:

                if (!empty($mediaGalleryImages)) {
                    foreach ($mediaGalleryImages as $mediaGalleryImage) {
                        $this->writeArray = &$returnArray['images'][];
                        foreach ($mediaGalleryImage->getData() as $key => $value) {
                            if ($key == 'url' && $usesPubFolder) {
                                $value = str_replace('/pub/', '/', $value);
                            }
                            $this->writeValue($key, $value);
                        }
                    }
                }
            }
            $this->writeArray = & $originalWriteArray;
        }

        // Get custom options
        if ($this->fieldLoadingRequired('custom_options') && !$exportAllFields) {
            $returnArray['custom_options'] = [];
            $originalWriteArray = & $this->writeArray;
            $this->writeArray = & $returnArray['custom_options'];
            // Unfortunately you can only fetch custom options with the product being loaded.
            //No way to add all the fields on collection load.
            $productCopy = $this->productRepository->getById($product->getId(), false, $this->getStoreId());
            // NOTE: If this doesn't work, we should try emulating environment like in the M1 version
            $productOptions = $productCopy->getOptions();
            if (is_array($productOptions)) {
                foreach ($productOptions as $productOption) {
                    $customOption = & $returnArray['custom_options'][];
                    $this->writeArray = & $customOption;
                    foreach ($productOption->getData() as $key => $value) {
                        $this->writeValue($key, $value);
                    }
                    $optionValues = $productOption->getValues();
                    if (is_array($optionValues)) {
                        $this->writeArray = & $customOption['values'];
                        foreach ($optionValues as $optionValue) {
                            $this->writeArray = & $customOption['values'][];
                            foreach ($optionValue->getData() as $key => $value) {
                                $this->writeValue($key, $value);
                            }
                        }
                    }
                }
            }
            $this->writeArray = & $originalWriteArray;
        }

        // Tier prices
        if ($this->fieldLoadingRequired('tier_prices') && !$exportAllFields) {
            $returnArray['tier_prices'] = [];
            $originalWriteArray = & $this->writeArray;
            $this->writeArray = & $returnArray['tier_prices'];
            $attribute = $product->getResource()->getAttribute('tier_price');

            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $tierPrices = $product->getData('tier_price');
                if (is_array($tierPrices)) {
                    foreach ($tierPrices as $tierPrice) {
                        $tierPriceNode = & $returnArray['tier_prices'][];
                        $this->writeArray = & $tierPriceNode;
                        foreach ($tierPrice as $key => $value) {
                            $this->writeValue($key, $value);
                        }
                    }
                }
            }
            $this->writeArray = & $originalWriteArray;
        }

        // Group prices
        if ($this->fieldLoadingRequired('group_prices') && !$exportAllFields) {
            $returnArray['group_prices'] = [];
            $originalWriteArray = & $this->writeArray;
            $this->writeArray = & $returnArray['group_prices'];
            $attribute = $product->getResource()->getAttribute('group_price');

            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $groupPrices = $product->getData('group_price');
                if (is_array($groupPrices)) {
                    foreach ($groupPrices as $groupPrice) {
                        $groupPriceNode = & $returnArray['group_prices'][];
                        $this->writeArray = & $groupPriceNode;
                        foreach ($groupPrice as $key => $value) {
                            $this->writeValue($key, $value);
                        }
                    }
                }
            }
            $this->writeArray = & $originalWriteArray;
        }
    }

    /**
     * Add Tax Method
     *
     * @param Product $product
     * @param float $price
     * @param string $key
     *
     * @return int
     */
    protected function addTax($product, $price, $key)
    {
        if ($product->getTaxPercent()) {
            $taxPercent = $product->getTaxPercent();
        } else {
            $taxPercent = false;
            if ($product->getTypeId() == 'grouped') {
                // Get tax_percent from child product
                $childProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
                if (is_array($childProductIds)) {
                    $childProductIds = array_shift($childProductIds);
                    if (is_array($childProductIds)) {
                        $childProductId = array_shift($childProductIds);
                        try {
                            $childProduct = $this->productRepository->getById(
                                $childProductId,
                                false,
                                $this->getStoreId()
                            );
                            if ($childProduct->getId()) {
                                $request = $this->taxCalculation->getRateRequest(
                                    false,
                                    false,
                                    false,
                                    $product->getStore()
                                );
                                $taxPercent = $this->taxCalculation->getRate(
                                    $request->setProductClassId($childProduct->getTaxClassId())
                                );
                            }
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                            $e->getMessage();
                        }
                    }
                }
            }
        }
        if ($taxPercent > 0) {
            if (!$this->taxConfig->priceIncludesTax($this->getStoreId())) {
                // Write price excl. tax
                $this->writeValue($key . '_excl_tax', $price);
                // Prices are excluding tax -> add tax
                $price *= 1 + $taxPercent / 100;
            } else {
                // Prices are including tax - do not add tax to price
                // Write price excl. tax
                $this->writeValue($key . '_excl_tax', $price / (1 + $taxPercent / 100));
            }
        } else {
            $this->writeValue($key . '_excl_tax', $price);
        }
        return $price;
    }
}
