<?php

/**
 * @package     Infosys/SignifydFingerprintCart
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\SignifydFingerprintCart\Plugin;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\History as HistoryResourceModel;
use Magento\Sales\Model\Order\Email\Sender\OrderCommentSender;
use Signifyd\Connect\Model\CasedataFactory;
use Signifyd\Connect\Model\ResourceModel\Casedata as CasedataResourceModel;
use Signifyd\Connect\Logger\Logger;

/**
 * Class to update order history comments
 */
class OrderHelper
{
    protected HistoryFactory $historyFactory;

    protected HistoryResourceModel $historyResourceModel;

    protected OrderCommentSender $orderCommentSender;

    protected CasedataFactory $casedataFactory;

    protected CasedataResourceModel $casedataResourceModel;

    protected Logger $logger;

    /**
     * Constructor function
     *
     * @param HistoryFactory $historyFactory
     * @param HistoryResourceModel $historyResourceModel
     * @param OrderCommentSender $orderCommentSender
     * @param CasedataFactory $casedataFactory
     * @param CasedataResourceModel $casedataResourceModel
     * @param Logger $logger
     */
    public function __construct(
        HistoryFactory $historyFactory,
        HistoryResourceModel $historyResourceModel,
        OrderCommentSender $orderCommentSender,
        CasedataFactory $casedataFactory,
        CasedataResourceModel $casedataResourceModel,
        Logger $logger
    ) {
        $this->historyFactory = $historyFactory;
        $this->historyResourceModel = $historyResourceModel;
        $this->orderCommentSender = $orderCommentSender;
        $this->casedataFactory = $casedataFactory;
        $this->casedataResourceModel = $casedataResourceModel;
        $this->logger = $logger;
    }

    /**
     * Add a comment history to a order without saving the order object
     *
     * @param \Signifyd\Connect\Helper\OrderHelper $subject
     * @param \Closure $proceed
     * @param Order $order
     * @param mixed $comment
     * @param boolean $isVisibleOnFront
     * @param boolean $isPassive
     * @return void
     */
    public function aroundAddCommentToStatusHistory(
        \Signifyd\Connect\Helper\OrderHelper $subject,
        \Closure $proceed,
        Order $order,
        $comment,
        $isVisibleOnFront = false,
        $isPassive = false
    ) {
        try {
            $comment = $isPassive ? 'PASSIVE: ' . $comment : $comment;
            $history = $this->historyFactory->create();
            $history->setStatus($order->getStatus());
            $history->setComment($comment);
            $history->setEntityName('order');
            $history->setIsVisibleOnFront($isVisibleOnFront);
            $history->setOrder($order);
            //Sending an email to customer when signifyd canceled the order
            $case = $this->casedataFactory->create();
            $this->casedataResourceModel->load($case, $order->getId(), 'order_id');
            $this->logger->info('Order Status'. $order->getStatus());
            $this->logger->info('Guarantee Status'. $case->getGuarantee());
            if ($order->getStatus() == 'canceled'  && $case->getGuarantee() == "REJECT") {
                $this->logger->info('Your Order was canceled because it was declined by fraud protection');
                $comment = "Your Order was canceled because it was declined by fraud protection";
                $this->orderCommentSender->send($order, true, $comment);
                $history->setIsCustomerNotified(true);
            }

            $this->historyResourceModel->save($history);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
