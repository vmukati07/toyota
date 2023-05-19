<?php
/**
 * @package     Infosys/StorePickUp
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\StorePickUp\Plugin\Sales\Block\Adminhtml\Order;

use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    const SHIPPING_METHOD_NAME ='dealerstore_pickup';
    
    const SHIPPMENT_CREATED_MSG ='Are you sure you want to notify the customer that order is ready for pickup?';

    const SHIPPMENT_NOT_CREATED_MSG ='Are you sure you want to notify the customer that order is ready for pickup and fulfill the order?';
   
    /**
     * @var MessageManager
     */
    private ManagerInterface $messageManager;

    /**
     * @var orderRepository
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param MessageManager $messageManager
     */
    public function __construct(
      OrderRepositoryInterface $orderRepository,
      ManagerInterface $messageManager
    ) {
      $this->orderRepository = $orderRepository;
      $this->messageManager = $messageManager;
    }

    /**
     *  Rendering Ready for Pickup button if shipping method is In Store Pickup
     */
    public function beforeSetLayout(OrderView $subject)
    {
      
        $order = $this->initOrder($subject->getOrderId());
        if (!$this->isDisplayButton($order)) {
            return;
        }
        
        $message = $this->isShipmentCreated($order) ? __(self::SHIPPMENT_CREATED_MSG) : __(self::SHIPPMENT_NOT_CREATED_MSG);
        $subject->addButton(
                'order_notify_email_button',
                [
                    'label' => __('Notify Order is Ready for Pickup'),
                    'class' => __('custom-button'),
                    'id' => 'order-view-custom-button',
                    'onclick' => sprintf(
                    "confirmSetLocation('%s', '%s')",
                    $message,
                    $subject->getUrl('storepickup/*/inStorePickUpNotify')
                ),
                ]
        );
        $subject->removeButton('order_ship');
    }

    /**
     * Initialize order model instance
     *
     * @return OrderInterface
     */
    private function initOrder($orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            return $order;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Unable to load this order.'));
            throw $e;
        }
    }

    /**
     * Check if shipping method is In Store Pickup.
     *
     * @return bool
     */
    private function isDisplayButton($order) : bool
    {
        return !$order->isCanceled() && $order->getState() !== Order::STATE_CLOSED && $order->getShippingMethod() == self::SHIPPING_METHOD_NAME && $order->getState() !== Order::STATE_HOLDED;
    }

    /**
     * Check if shipment is created or not
     *
     * @return bool
     */
     private function isShipmentCreated($order):bool
     {
        return $order->hasShipments();
     }

}