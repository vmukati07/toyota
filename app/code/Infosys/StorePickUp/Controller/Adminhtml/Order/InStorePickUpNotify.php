<?php

/**
 * @package     Infosys/StorePickUp
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\StorePickUp\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Notify Customer of order pickup availability.
 */
class InStorePickUpNotify extends Action implements HttpGetActionInterface
{
    public const SUCCESS_MESSAGE = 'The customer has been notified.';

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::emails';

    /**
     * @var NotifyOrdersAreReadyForPickupInterface
     */
    private $notifyOrdersAreReadyForPickup;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param Context $context
     * @param NotifyOrdersAreReadyForPickupInterface $notifyOrdersAreReadyForPickup
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        NotifyOrdersAreReadyForPickupInterface $notifyOrdersAreReadyForPickup,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->notifyOrdersAreReadyForPickup = $notifyOrdersAreReadyForPickup;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Notify customer by email
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $order = $this->initOrder();
        } catch (LocalizedException $e) {
            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }
        $result = $this->notifyOrdersAreReadyForPickup->execute([(int)$order->getEntityId()]);
        if ($result->isSuccessful()) {
            $this->messageManager->addSuccessMessage(__(self::SUCCESS_MESSAGE));
        } else {
            $error = current($result->getErrors());
            $this->messageManager->addErrorMessage($error['message']);
        }

        return $this->resultRedirectFactory->create()->setPath(
            'sales/order/view',
            [
                'order_id' => $order->getEntityId(),
            ]
        );
    }

    /**
     * Initialize order model instance
     *
     * @return OrderInterface
     * @throws InputException
     * @throws NoSuchEntityException
     * @see \Magento\Sales\Controller\Adminhtml\Order::_initOrder
     */
    private function initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException | InputException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            throw $e;
        }
        return $order;
    }
}
