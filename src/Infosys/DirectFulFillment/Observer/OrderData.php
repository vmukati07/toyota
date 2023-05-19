<?php

/**
 * @package     Infosys/DirectFulFillment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\DirectFulFillment\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Infosys\DirectFulFillment\Model\RejectedNotificationEmail;
use Infosys\DirectFulFillment\Model\Config\Configurations;

class OrderData implements \Magento\Framework\Event\ObserverInterface
{
    const ADD_ETA_ORDER_COMMENT = 'df_config/backorder_eta/add_eta_order_comment';

    /**
     * @var itemLevelComment
     */
    public $itemLevelComment;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $config;

    /**
     * @var RejectedNotificationEmail
     */
    protected RejectedNotificationEmail $rejectedEmailSender;

    /**
     * @var Configurations
     */
    protected Configurations $storeConfig;

    /**
     * Constructor function
     *
     * @param ScopeConfigInterface $config
     * @param RejectedNotificationEmail $rejectedEmailSender
     * @param Configurations $storeConfig
     */
    public function __construct(
        ScopeConfigInterface $config,
        RejectedNotificationEmail $rejectedEmailSender,
        Configurations $storeConfig
    ) {
        $this->config = $config;
        $this->rejectedEmailSender = $rejectedEmailSender;
        $this->storeConfig = $storeConfig;
    }

    /**
     * Add Order data during import
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderData = $observer->getData('update_data');
        $order = $observer->getData('order');
        if (isset($orderData['order_reference'])) {
            $order->setOrderReference($orderData['order_reference']);
        }
        $this->itemLevelComment = '';
        foreach ($order->getAllItems() as $orderItem) {
            if (isset($orderData['items']) && !empty($orderData['items'])) {
                foreach ($orderData['items'] as $itemRecord) {
                    if (
                        isset($itemRecord['sku']) &&
                        strtolower($orderItem->getSku()) == strtolower($itemRecord['sku'])
                    ) {
                        if (isset($itemRecord['direct_fulfillment_status']) &&  ($orderItem->getDirectFulfillmentStatus() != 'SHIPPED')) {
                            if (isset($itemRecord['order_history_comment_item_level']) && !empty($itemRecord['order_history_comment_item_level'])) {
                                $orderItem->setDirectFulfillmentStatus($itemRecord['direct_fulfillment_status'] . ' | ' . $itemRecord['order_history_comment_item_level']);
                                $orderItem->setDirectFulfillmentResponse($itemRecord['order_history_comment_item_level']);
                                if ($this->config->isSetFlag(self::ADD_ETA_ORDER_COMMENT)) {
                                    $this->itemLevelComment .= $orderItem->getName() . ' ' . $itemRecord['direct_fulfillment_status'] . ' ' . $itemRecord['order_history_comment_item_level'] . ' | ';
                                }
                                if (
                                    $this->storeConfig->isRejectedEmailsEnabled($order->getStoreId()) &&
                                    $itemRecord['direct_fulfillment_status'] == 'REJECTED' ||
                                    $itemRecord['direct_fulfillment_status'] == 'CANCELLED'
                                ) {
                                    $this->rejectedEmailSender->sendRejectedNotificationEmail($order, $itemRecord);
                                }
                            } else {
                                $orderItem->setDirectFulfillmentStatus($itemRecord['direct_fulfillment_status']);
                            }
                        }
                    }
                    $orderItem->save();
                }
            }
        }
        if (!empty($this->itemLevelComment)) {
            $order->addStatusHistoryComment(substr($this->itemLevelComment, 0, -3));
        }
        $order->save();
    }
}
