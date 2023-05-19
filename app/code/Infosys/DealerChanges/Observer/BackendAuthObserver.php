<?php

/**
 * @package     Infosys/DealerChanges
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerChanges\Observer;

use Magento\Ui\Api\BookmarkManagementInterface;
use Magento\Ui\Api\BookmarkRepositoryInterface;
use Magento\Ui\Api\Data\BookmarkInterface;
use Magento\Ui\Api\Data\BookmarkInterfaceFactory;
use Magento\Ui\Controller\Adminhtml\AbstractAction;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Backend\Model\Auth\Session;

/**
 *  Event after admin login
 */
class BackendAuthObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var BookmarkRepositoryInterface
     */
    protected BookmarkRepositoryInterface $bookmarkRepository;
    /**
     * @var BookmarkManagementInterface
     */
    protected BookmarkManagementInterface $bookmarkManagement;
    /**
     * @var BookmarkInterfaceFactory
     */
    protected BookmarkInterfaceFactory $bookmarkFactory;
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;
        /**
     * @var Session
     */
    private Session $session;

    /**
     * Constructor function
     *
     * @param BookmarkRepositoryInterface $bookmarkRepository
     * @param BookmarkManagementInterface $bookmarkManagement
     * @param BookmarkInterfaceFactory $bookmarkFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $session
     */
    public function __construct(
        BookmarkRepositoryInterface $bookmarkRepository,
        BookmarkManagementInterface $bookmarkManagement,
        BookmarkInterfaceFactory $bookmarkFactory,
        ScopeConfigInterface $scopeConfig,
        Session $session
    ) {
        $this->bookmarkRepository = $bookmarkRepository;
        $this->bookmarkManagement = $bookmarkManagement;
        $this->bookmarkFactory = $bookmarkFactory;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
    }

    /**
     * Sync on admin login after
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $user = $observer->getEvent()->getUser();
        $user_id = $user->getData('user_id');
        $role_id = $this->session->getUser()->getRole()->getData('role_id');
        $ConfigData = $this->scopeConfig->getValue('dealer_changes/dealer_grid_columns/dealer_grid_data',\Magento\Store\Model\ScopeInterface::SCOPE_STORE,null);
        $dealerManagerRole = $this->scopeConfig->getValue('pitbulk_saml2_admin/options/dealer_program_manager_role_id');            
        $dealerOrderRole = $this->scopeConfig->getValue('pitbulk_saml2_admin/options/dealer_order_processing_role_id');

        if(!empty($ConfigData)){
            $currentConfigData = '{"current":'.$ConfigData.'}';
            $defaultConfigData = '{"views":{"default":{"label":"Dealer Default ","index":"default","editable":false,"data":'.$ConfigData.',"value":"Dealer Default "}}}';    
        }else{
            $currentConfigData = '{"current":{"paging":{"pageSize":20,"current":1,"options":{"20":{"value":20,"label":20},"30":{"value":30,"label":30},"50":{"value":50,"label":50},"100":{"value":100,"label":100},"200":{"value":200,"label":200}},"value":20},"columns":{"increment_id":{"visible":true,"sorting":false},"store_id":{"visible":false,"sorting":false},"billing_name":{"visible":true,"sorting":false},"shipping_name":{"visible":true,"sorting":false},"base_grand_total":{"visible":false,"sorting":false},"grand_total":{"visible":true,"sorting":false},"billing_address":{"visible":true,"sorting":false},"shipping_address":{"visible":true,"sorting":false},"shipping_information":{"visible":true,"sorting":false},"customer_email":{"visible":true,"sorting":false},"subtotal":{"visible":true,"sorting":false},"shipping_and_handling":{"visible":true,"sorting":false},"customer_name":{"visible":false,"sorting":false},"total_refunded":{"visible":false,"sorting":false},"refunded_to_store_credit":{"visible":false,"sorting":false},"pickup_location_code":{"visible":false,"sorting":false},"customer_central_id":{"visible":false,"sorting":false},"carrier_group":{"visible":false,"sorting":false},"time_slot":{"visible":false,"sorting":false},"pickup_location":{"visible":false,"sorting":false},"carrier_type":{"visible":true,"sorting":false},"shipping_method":{"visible":true,"sorting":false},"transaction_source":{"visible":false,"sorting":false},"tj_salestax_sync_date":{"visible":false,"sorting":false},"direct_fulfillment_status":{"visible":true,"sorting":false},"service_fee":{"visible":false,"sorting":false},"allocated_sources":{"visible":false,"sorting":false},"actions":{"visible":false,"sorting":false},"signifyd_score":{"visible":false,"sorting":false},"signifyd_guarantee":{"visible":true,"sorting":false},"checkpoint_action_reason":{"visible":false,"sorting":false},"ids":{"visible":true,"sorting":false},"status":{"visible":true,"sorting":false},"customer_group":{"visible":false,"sorting":false},"payment_method":{"visible":false,"sorting":false},"created_at":{"visible":true,"sorting":"desc"},"delivery_date":{"visible":false,"sorting":false},"dispatch_date":{"visible":false,"sorting":false}},"displayMode":"grid","positions":{"ids":0,"increment_id":1,"store_id":2,"created_at":3,"status":4,"shipping_information":5,"billing_name":6,"shipping_name":7,"base_grand_total":8,"subtotal":9,"shipping_and_handling":10,"grand_total":11,"billing_address":12,"carrier_group":13,"delivery_date":14,"dispatch_date":15,"time_slot":16,"pickup_location":17,"shipping_address":18,"carrier_type":19,"customer_email":20,"customer_group":21,"customer_name":22,"payment_method":23,"total_refunded":24,"actions":25,"refunded_to_store_credit":26,"allocated_sources":27,"pickup_location_code":28,"customer_central_id":29,"direct_fulfillment_status":30,"shipping_method":36,"transaction_source":32,"signifyd_score":33,"signifyd_guarantee":34,"checkpoint_action_reason":35,"tj_salestax_sync_date":36,"service_fee":37},"search":{"value":""},"filters":{"applied":{"placeholder":true}}}}';
            $defaultConfigData = '{"views":{"default":{"label":"Default Dealer","index":"default","editable":false,"data":{"paging":{"pageSize":20,"current":1,"options":{"20":{"value":20,"label":20},"30":{"value":30,"label":30},"50":{"value":50,"label":50},"100":{"value":100,"label":100},"200":{"value":200,"label":200}},"value":20},"columns":{"increment_id":{"visible":true,"sorting":false},"store_id":{"visible":false,"sorting":false},"billing_name":{"visible":true,"sorting":false},"shipping_name":{"visible":true,"sorting":false},"base_grand_total":{"visible":false,"sorting":false},"grand_total":{"visible":true,"sorting":false},"billing_address":{"visible":true,"sorting":false},"shipping_address":{"visible":true,"sorting":false},"shipping_information":{"visible":true,"sorting":false},"customer_email":{"visible":true,"sorting":false},"subtotal":{"visible":true,"sorting":false},"shipping_and_handling":{"visible":true,"sorting":false},"customer_name":{"visible":false,"sorting":false},"total_refunded":{"visible":false,"sorting":false},"refunded_to_store_credit":{"visible":false,"sorting":false},"pickup_location_code":{"visible":false,"sorting":false},"customer_central_id":{"visible":false,"sorting":false},"carrier_group":{"visible":false,"sorting":false},"time_slot":{"visible":false,"sorting":false},"pickup_location":{"visible":false,"sorting":false},"carrier_type":{"visible":true,"sorting":false},"shipping_method":{"visible":true,"sorting":false},"transaction_source":{"visible":false,"sorting":false},"tj_salestax_sync_date":{"visible":false,"sorting":false},"direct_fulfillment_status":{"visible":true,"sorting":false},"service_fee":{"visible":false,"sorting":false},"allocated_sources":{"visible":false,"sorting":false},"actions":{"visible":false,"sorting":false},"signifyd_score":{"visible":false,"sorting":false},"signifyd_guarantee":{"visible":true,"sorting":false},"checkpoint_action_reason":{"visible":false,"sorting":false},"ids":{"visible":true,"sorting":false},"status":{"visible":true,"sorting":false},"customer_group":{"visible":false,"sorting":false},"payment_method":{"visible":false,"sorting":false},"created_at":{"visible":true,"sorting":"desc"},"delivery_date":{"visible":false,"sorting":false},"dispatch_date":{"visible":false,"sorting":false}},"displayMode":"grid","positions":{"ids":0,"increment_id":1,"store_id":2,"created_at":3,"status":4,"shipping_information":5,"billing_name":6,"shipping_name":7,"base_grand_total":8,"subtotal":9,"shipping_and_handling":10,"grand_total":11,"billing_address":12,"carrier_group":13,"delivery_date":14,"dispatch_date":15,"time_slot":16,"pickup_location":17,"shipping_address":18,"carrier_type":19,"customer_email":20,"customer_group":21,"customer_name":22,"payment_method":23,"total_refunded":24,"actions":25,"refunded_to_store_credit":26,"allocated_sources":27,"pickup_location_code":28,"customer_central_id":29,"direct_fulfillment_status":30,"shipping_method":36,"transaction_source":32,"signifyd_score":33,"signifyd_guarantee":34,"checkpoint_action_reason":35,"tj_salestax_sync_date":36,"service_fee":37},"search":{"value":""},"filters":{"applied":{"placeholder":true}}},"value":"Default Dealer"}}}';
        }

        if (($role_id == $dealerManagerRole) || ($role_id == $dealerOrderRole)) {
            $currentBookmarkData = $this->checkBookmark('current');
            $defaultBookmarkData = $this->checkBookmark('default');
            $namespace = 'sales_order_grid';
            $currentIdentifier = 'current';
            $defaultIdentifier = 'default';
            $title = 'Dealer Default ';

            if ($currentBookmarkData !== false) {
                $currentBookmark = $currentBookmarkData;
            }else{
                $currentBookmark = $this->bookmarkFactory->create();
            }
            $currentBookmark->setUserId($user_id)
                ->setNamespace($namespace)
                ->setIdentifier($currentIdentifier)
                ->setCurrent('0')
                ->setConfig($currentConfigData);
            $this->bookmarkRepository->save($currentBookmark);

            if ($defaultBookmarkData !== false) {
                $defaultBookmark = $defaultBookmarkData;
            }else{
                $defaultBookmark = $this->bookmarkFactory->create();
            }
            $defaultBookmark->setUserId($user_id)
                ->setNamespace($namespace)
                ->setIdentifier($defaultIdentifier)
                ->setCurrent('1')
                ->setTitle($title)
                ->setConfig($defaultConfigData);
            $this->bookmarkRepository->save($defaultBookmark);
        }

        return $this;
    }

    /**
     * Check bookmark function
     *
     * @return void
     */
    public function checkBookmark($identifier)
    {
        $result = false;

        $updateBookmark = $this->bookmarkManagement->getByIdentifierNamespace(
            $identifier,
            'sales_order_grid'
        );

        if ($updateBookmark) {
            $result = $updateBookmark;
        }

        return $result;
    }
}
