<?php
/**
 * @package     Infosys/CoreChargeAttribute
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
namespace Infosys\CoreChargeAttribute\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeGroupInterfaceFactory;
use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;

/**
 * Product attribute data patch
 */
class ProductAttribute implements DataPatchInterface
{
     /**
      * @var EavSetupFactory
      */
    private $eavSetupFactory;

     /**
      * @var AttributeSetFactory
      */
    private $attributeSetFactory;

     /**
      * @var Product
      */
    private $product;

     /**
      * @var AttributeGroupInterfaceFactory
      */
    private $attributeGroupFactory;

     /**
      * @var AttributeSetManagementInterface
      */
    private $attributeSetManagement;

     /**
      * @var AttributeGroupRepositoryInterface
      */
    private $attributeGroupRepository;

     /**
      * @var ModuleDataSetupInterface
      */
    private $moduleDataSetup;
    
    /**
     * Constuctor function
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributeSetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     * @param Product $product
     * @param AttributeSetManagementInterface $attributeSetManagement
     * @param AttributeGroupInterfaceFactory $attributeGroupFactory
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AttributeSetFactory $attributeSetFactory,
        EavSetupFactory $eavSetupFactory,
        Product $product,
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeGroupInterfaceFactory $attributeGroupFactory,
        AttributeGroupRepositoryInterface $attributeGroupRepository
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->product = $product;
        $this->attributeSetManagement = $attributeSetManagement;
        $this->attributeGroupFactory = $attributeGroupFactory;
        $this->attributeGroupRepository = $attributeGroupRepository;
    }
    /**
     * Patch to create Product Attributes
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $attributes = [
            'core_charge' => [
                    'group_name' => 'PDP Custom Attributes',
                    'type' => 'decimal',
                    'label' => 'Core Charge',
                    'input' => 'price',
                    'source' => '',
                    'option' => '',
                    'filterable' => true,
                    'searchable' => false,
                    'visible_on_front' => true,
                    'used_in_product_listing' => true,
                    'backend' => ''
            ]
        ];
        $this->createProductAttribute($attributes);
        $this->moduleDataSetup->endSetup();
    }
    /**
     * Method to create Product Attributes
     *
     * @param array $attributes
     */
    private function createProductAttribute($attributes)
    {
        $eavSetup = $this->eavSetupFactory->create();
        $productEntity = \Magento\Catalog\Model\Product::ENTITY;
        $attributeSetId = $this->product->getDefaultAttributeSetId();
        foreach ($attributes as $attribute => $data) {
            $eavSetup = $this->eavSetupFactory->create();
            
            $attributeGroupName = $data['group_name'];
            /**
             * creating Attribute Groups
             */
            if (!$eavSetup->getAttributeGroup($productEntity, $attributeSetId, $attributeGroupName)) {
                $this->createAttributeGroup($attributeGroupName, $attributeSetId);
            }
            $attributeGroupId = $eavSetup->getAttributeGroupId($productEntity, $attributeSetId, $attributeGroupName);
            /**
             * Add attributes to the eav/attribute
             */
            if (!$eavSetup->getAttributeId($productEntity, $attribute)) {
                $eavSetup->addAttribute(
                    $productEntity,
                    $attribute,
                    [
                        'group' => $attributeGroupId ? '' : 'General',
                        // Let empty, if we want to set an attribute group id
                        'type' => $data['type'],
                        'backend' => $data['backend'],
                        'frontend' => '',
                        'label' => $data['label'],
                        'input' => $data['input'],
                        'class' => '',
                        'source' => $data['source'],
                        'option' => $data['option'],
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => false,
                        'required' => false,
                        'user_defined' => true,
                        'default' => '',
                        'searchable' => $data['searchable'],
                        'filterable' => $data['filterable'],
                        'comparable' => false,
                        'visible_on_front' => $data['visible_on_front'],
                        'used_in_product_listing' => $data['used_in_product_listing'],
                        'unique' => false,
                        'apply to' => ''
                    ]
                );
                if ($attributeGroupId!==null) {
                    /**
                    * Set the attribute in the right attribute group in the right attribute set
                    */
                    $eavSetup->addAttributeToGroup($productEntity, $attributeSetId, $attributeGroupId, $attribute);
                }

            }
        }
    }

    /**
     * Method to create Attribute Group
     *
     * @param string $attributeGroupName
     * @param string $attributeSetId
     */
    private function createAttributeGroup($attributeGroupName, $attributeSetId)
    {
        $attributeGroup = $this->attributeGroupFactory->create();
        $attributeGroup->setAttributeSetId($attributeSetId);
        $attributeGroup->setAttributeGroupName($attributeGroupName);
        $this->attributeGroupRepository->save($attributeGroup);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
