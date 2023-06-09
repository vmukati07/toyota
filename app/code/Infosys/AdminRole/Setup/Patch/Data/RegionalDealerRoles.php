<?php

/**
 * @package     Infosys/AdminRole
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\AdminRole\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;

class RegionalDealerRoles implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var RoleFactory
     */
    private $roleFactory;
    /**
     * @var RulesFactory
     */
    private $rulesFactory;
    /**

     * @var AclRetriever
     */
    private $acl;
    /**
     * Constructor function
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RoleFactory $roleFactory
     * @param RulesFactory $rulesFactory
     * @param AclRetriever $_acl
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RoleFactory $roleFactory,
        RulesFactory $rulesFactory,
        AclRetriever $_acl
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->roleFactory = $roleFactory;
        $this->rulesFactory = $rulesFactory;
        $this->acl = $_acl;
    }
    /**
     * Patch to create user roles and permissions
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $role = $this->roleFactory->create();
        $collection = $role->getCollection();
        foreach ($collection as $avroles) {
            $availableRoles[] = $avroles->getRoleName();
        }
        $newRoles = [
            'PCO eCommerce Corporate Regional' => ['Infosys_PriceAdjustment::add',
                'Infosys_PriceAdjustment::price_ad','Magento_AdvancedCheckout::magento_advancedcheckout',
                'Magento_AdvancedCheckout::update','Magento_AdvancedCheckout::view',
                'Magento_Analytics::analytics','Magento_Analytics::business_intelligence',
                'Magento_Backend::admin','Magento_Backend::dashboard','Magento_Backend::global_search',
                'Magento_Backend::marketing','Magento_Backend::stores','Magento_Backend::stores_other_settings',
                'Magento_Backend::system','Magento_Cart::cart',
				'Magento_Catalog::catalog', 'Magento_Catalog::catalog_inventory', 'Magento_Catalog::products', 'Magento_PricePermissions::read_product_price', 'Magento_PricePermissions::edit_product_price', 'Magento_PricePermissions::edit_product_status', 'Magento_Catalog::update_attributes', 'Magento_Catalog::edit_product_design', 'Magento_PromotionPermissions::quote_edit',  
                'Magento_Cart::manage','Magento_CatalogRule::promo','Magento_Customer::customer',
                'Magento_Customer::manage','Magento_InventoryApi::inventory',
                'Magento_InventoryInStorePickupApi::inStorePickup',
                'Magento_InventoryInStorePickupApi::notify_orders_are_ready_for_pickup','Magento_Newsletter::problem',
                'Magento_Paypal::actions_manage','Magento_Paypal::authorization',
                'Magento_Paypal::billing_agreement',
                'Magento_Paypal::billing_agreement_actions','Magento_Paypal::billing_agreement_actions_view',
                'Magento_Paypal::use','Magento_Reports::report','Magento_Reports::report_marketing',
                'Magento_Reports::report_products','Magento_Reports::report_search','Magento_Reports::salesroot',
                'Magento_Reports::shopcart','Magento_Rma::magento_rma','Magento_Sales::actions',
                'Magento_Sales::actions_edit','Magento_Sales::actions_view',
                'Magento_Sales::cancel','Magento_Sales::capture','Magento_Sales::comment',
                'Magento_Sales::create','Magento_Sales::creditmemo','Magento_Sales::email','Magento_Sales::emails',
                'Magento_Sales::hold','Magento_Sales::invoice','Magento_Sales::reorder','Magento_Sales::review_payment',
                'Magento_Sales::sales','Magento_Sales::sales_creditmemo',
                'Magento_Sales::sales_invoice','Magento_Sales::sales_operation','Magento_Sales::sales_order',
                'Magento_Sales::ship','Magento_Sales::shipment','Magento_Sales::transactions',
                'Magento_Sales::transactions_fetch','Magento_Sales::unhold','Magento_SalesArchive::add',
                'Magento_SalesArchive::archive','Magento_SalesArchive::creditmemos','Magento_SalesArchive::invoices',
                'Magento_SalesArchive::orders','Magento_SalesArchive::remove','Magento_SalesArchive::shipments',
                'Magento_SalesRule::quote','Magento_User::acl','Magento_User::locks',
                'ShipperHQ_Shipper::createlisting','ParadoxLabs_TokenBase::cards_manage','Magento_Reports::customers',
                'Magento_Reports::customers_orders','Magento_Reports::totals'],
        ];
        $keys = array_keys($newRoles);
        foreach ($keys as $newrole) {
            if (!(in_array($newrole, $availableRoles))) {
                $role = $this->roleFactory->create();
                $role->setName($newrole)
                    ->setPid(0)
                    ->setRoleType(RoleGroup::ROLE_TYPE)
                    ->setUserType(UserContextInterface::USER_TYPE_ADMIN);
                $role->save();

                $resources = $newRoles[$newrole];

                $this->rulesFactory->create()
                    ->setRoleId($role->getId())
                    ->setResources($resources)
                    ->saveRel();
            } else {
                foreach ($collection as $var) {
                    if ($var->getRoleName() == $newrole) {
                        $roleId = $var->getId();
                        break;
                    }
                }
                $newResources = $newRoles[$newrole];
                $oldResources = $this->acl->getAllowedResourcesByRole($roleId);
                $resources = array_merge($oldResources, $newResources);
                $this->rulesFactory->create()
                    ->setRoleId($roleId)
                    ->setResources($resources)
                    ->saveRel();
            }
        }
        $this->moduleDataSetup->endSetup();
    }
    /**
     * Aliases function
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
    /**
     * Dependencies function
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }
}
