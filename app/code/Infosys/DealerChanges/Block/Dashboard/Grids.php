<?php

/**
 * @package     Infosys/DealerChanges
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerChanges\Block\Dashboard;

use Magento\Backend\Block\Dashboard\Grids as CoreGrids;
use Magento\Backend\Block\Dashboard\Tab\Products\Ordered;
use Infosys\DealerChanges\Model\DashboardLinks\Permissions;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Json\EncoderInterface;
use Magento\Backend\Model\Auth\Session;

/**
 * Class to customize the dashboard grids
 */
class Grids extends CoreGrids
{
    /**
     * @var Permissions
     */
    protected Permissions $permissions;

    /**
     * Construct function
     *
     * @param Context $context
     * @param EncoderInterface $encoderInterface
     * @param Session $session
     * @param Permissions $permissions
     * @param array $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $encoderInterface,
        Session $session,
        Permissions $permissions,
        array $data = []
    ) {
        $this->permissions = $permissions;
        parent::__construct($context, $encoderInterface, $session, $data);
    }

    /**
     * _prepareLayout function to customize the dashboard grid
     *
     * @return void
     */
    protected function _prepareLayout() // @codingStandardsIgnoreLine - required by parent class
    {
        $this->addTab(
            'ordered_products',
            [
                'label' => __('Bestsellers'),
                'content' => $this->getLayout()->createBlock(
                    Ordered::class
                )->toHtml(),
                'active' => true
            ]
        );

        $this->addTab(
            'reviewed_products',
            [
                'label' => __('Most Viewed Products'),
                'url' => $this->getUrl('adminhtml/*/productsViewed', ['_current' => true]),
                'class' => 'ajax'
            ]
        );

        if ($this->permissions->checkNewCustomersTabPermission() == true) {
            $this->addTab(
                'new_customers',
                [
                    'label' => __('New Customers'),
                    'url' => $this->getUrl('adminhtml/*/customersNewest', ['_current' => true]),
                    'class' => 'ajax',
                    'visible' => false
                ]
            );
        }
        if ($this->permissions->checkCustomersTabPermission() == true) {
            $this->addTab(
                'customers',
                [
                    'label' => __('Customers'),
                    'url' => $this->getUrl('adminhtml/*/customersMost', ['_current' => true]),
                    'class' => 'ajax'
                ]
            );
        }

        if ($this->permissions->checkFastlyPermission() == true) {
            $this->addTab(
                'fastly_historic_stats',
                [
                    'label'     => __('Fastly'),
                    'url'       => $this->getUrl('adminhtml/dashboard/historic', ['_current' => true]),
                    'class'     => 'ajax',
                    'active'    => false
                ]
            );
        }
    }
}
