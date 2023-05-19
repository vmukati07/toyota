<?php

/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
declare (strict_types = 1);

namespace Infosys\PriceAdjustment\Model\Config\Source;

use Infosys\DealerChanges\Helper\Data as StoreHelper;
use Infosys\PriceAdjustment\Model\BrandProductTypeFactory;
use Infosys\PriceAdjustment\Model\Config\Source\SetProductTypeOptions;
use Infosys\ProductsByVin\Helper\Data as BrandHelper;
use Magento\Backend\Model\Auth\Session;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class for getting media set options
 */
class SetOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var BrandHelper
     */
    protected BrandHelper $brandHelper;
    
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
     
    /**
     * @var Product
     */
    protected Product $product;
    
    /**
     * @var SetProductTypeOptions
     */
    protected SetProductTypeOptions $attributeSet;
    
    /**
     * @var BrandProductTypeFactory
     */
    protected BrandProductTypeFactory $brandType;
     
    /**
     * @var StoreHelper
     */
    protected StoreHelper $storeHelper;
     
    /**
     * @var Session
     */
    protected Session $authSession;

    /**
     * Constructor function
     *
     * @param BrandHelper $brandHelper
     * @param StoreManagerInterface $storeManager
     * @param Product $product
     * @param SetProductTypeOptions $attributeSet
     * @param BrandProductTypeFactory $brandType
     * @param StoreHelper $storeHelper
     * @param Session $authSession
     */
    public function __construct(
        BrandHelper $brandHelper,
        StoreManagerInterface $storeManager,
        Product $product,
        SetProductTypeOptions $attributeSet,
        BrandProductTypeFactory $brandType,
        StoreHelper $storeHelper,
        Session $authSession
    ) {
        $this->brandHelper = $brandHelper;
        $this->storeManager = $storeManager;
        $this->product = $product;
        $this->attributeSet = $attributeSet;
        $this->brandType = $brandType;
        $this->storeHelper = $storeHelper;
        $this->authSession = $authSession;
    }

    /**
     * Get all options for media set
     *
     * @return array
     */
    public function getAllOptions(): array
    {
        $brand = [];
        $result = [];
        $storeId = '';
        $finalResult = [];
        $mediaSetSelector = [];
        if ($this->storeHelper->isDealerLogin()) {
            $storeId = $this->authSession->getUser()->getData('website_ids');
        } else {
            $storeId = $this->storeManager->getStore()->getId();
        }
        $storeBrand = $this->brandHelper->getEnabledBrands($storeId);
        if ($storeBrand) {
            $brand = explode(',', $storeBrand);
        }
        $brandModel = $this->brandType->create();
        $data = $brandModel->getCollection()
            ->addFieldToFilter('dealer_brand', ['in' => $brand])
            ->getData();

        if (!empty($data)) {
            foreach ($data as $mediaSet) {
                $media = $mediaSet['media_set_selector'] . '-' . $mediaSet['product_type'];
                if (!in_array($media, $mediaSetSelector)) {
                    $mediaSetSelector[] = $media;
                    $result['label'] = $this->getOptionText($mediaSet['media_set_selector']);
                    $result['value'] = $mediaSet['media_set_selector'];
                    $result['tier_price_product_type'] = $mediaSet['product_type'];
                    $finalResult[] = $result;
                }
            }
        }

        return $finalResult;
    }

    /**
     * Fetch option value by option id
     *
     * @param int $optionId
     * @return void
     */
    public function getOptionText($optionId)
    {
        $optionText = "";
        $attr = $this->product->getResource()->getAttribute('tier_price_set');
        if ($attr->usesSource()) {
            $optionText = $attr->getSource()->getOptionText($optionId);
        }
        
        return $optionText;
    }
}
