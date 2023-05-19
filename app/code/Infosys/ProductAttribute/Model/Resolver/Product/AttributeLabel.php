<?php

/**
 * @package Infosys/ProductAttribute
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright � 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ProductAttribute\Model\Resolver\Product;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Infosys\ProductsByVin\Helper\Data as BrandHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Resolver for dropdown attributes option lable
 */
class AttributeLabel implements ResolverInterface
{
    protected ProductRepository $productRepository;

    protected BrandHelper $brandHelper;

    protected Json $json;

    protected ScopeConfigInterface $scopeConfig;

    /**
     * Constructor function
     *
     * @param ProductRepository $productRepository
     * @param BrandHelper $brandHelper
     * @param Json $json
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductRepository $productRepository,
        BrandHelper $brandHelper,
        Json $json,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productRepository = $productRepository;
        $this->brandHelper = $brandHelper;
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get all dropdown attributes option label
     *
     * @param object $field
     * @param object $context
     * @param object $info
     * @param array $value
     * @param array $args
     * @return array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ProductInterface $product */
        $product = $value['model'];
        $attributes_data = [];
        $whatThisFits = '';
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $productId = $product->getId();
        $weightUnit = $this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE
        );
        
        if ($productId) {
            $product = $this->productRepository->getById($productId);
            $attributes = $product->getAttributes(); // All Product Attributes
            foreach ($attributes as $attribute) {
                // check if attribute code is weight or weight_type, set attribute as user defined
                if ($attribute->getAttributeCode() == 'weight' || $attribute->getAttributeCode() == 'weight_type') {
                    $attribute->setIsUserDefined(1);
                }
                    
                if ($attribute->getIsUserDefined()) {
                    //set attribute value for fitment data
                    if ($attribute->getAttributeCode() == 'what_this_fits') {
                        $whatThisFits = $this->getProductFitmentData($product, $storeId);
                    }
                    $attributeValue = ($attribute->getAttributeCode() != 'what_this_fits') ? $attribute->getFrontend()->getValue($product) : $whatThisFits;

                    //set store config value for weight_type attribute
                    if ($attribute->getAttributeCode() == 'weight_type') {
                        $attributeValue = $weightUnit;
                    }

                    //product visibility check
                    if ($attribute->getIsVisibleOnFront()) {
                        $visibility_status = 'Yes';
                    } else {
                        $visibility_status = 'No';
                    }

                    $attributeLabel = $attribute->getFrontend()->getLabel();
                    $attributes_data[] = [
                        'attribute_code' => $attribute->getAttributeCode(),
                        'attribute_label' => $attributeLabel,
                        'attribute_value' => $attributeValue,
                        'visibility_status' => $visibility_status
                    ];

                    $whatThisFits = '';
                }
            }
        }
        return $attributes_data;
    }

    /**
     * Method to get product fitment data based on store brand
     *
     * @param object $product
     * @param int $storeId
     * @return string
     */
    public function getProductFitmentData($product, $storeId): string
    {
        $dealer_brand = $this->brandHelper->getEnabledBrands($storeId);
        $resource = $product->getResource();

        //get what this fits attribute value for default store
        $whatThisFits = $resource->getAttributeRawValue($product->getId(), 'what_this_fits', 0);

        $productFitment = [];
        if ($dealer_brand && !empty($whatThisFits)) {
            $dealer_brand = explode(',', $dealer_brand);
            $fitmentData = $this->json->unserialize($whatThisFits);
            foreach ($dealer_brand as $brand) {
                if (array_key_exists($brand, $fitmentData)) {
                    $productFitment[] = $fitmentData[$brand];
                }
            }
        }

        $productFitment = $this->json->serialize($productFitment);
        return $productFitment;
    }
}
