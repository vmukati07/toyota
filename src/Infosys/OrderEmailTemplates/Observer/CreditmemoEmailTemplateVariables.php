<?php

/**
 * @package     Infosys/OrderEmailTemplates
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\OrderEmailTemplates\Observer;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Infosys\OrderEmailTemplates\Model\Config\Configuration;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 *  Set creditmemo refund amount email template custom variables
 */
class CreditmemoEmailTemplateVariables implements ObserverInterface
{
    protected LoggerInterface $logger;

    protected Configuration $config;

    protected TransportBuilder $transport;

    protected PriceHelper $priceHelper;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Initialize dependencies
     *
     * @param LoggerInterface $logger
     * @param Configuration $config
     */
    public function __construct(
        LoggerInterface $logger,
        Configuration $config,
        TransportBuilder $transport,
        PriceHelper $priceHelper,
        CreditmemoRepositoryInterface $creditmemoRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->transport = $transport;
        $this->priceHelper = $priceHelper;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Add custom variables in order email templates
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $transportObject = $observer->getEvent()->getData('transportObject');
            $order = $transportObject->getData('order');
            $storeId = $order->getStore()->getId();
            $orderData = $transportObject->getData('order_data');
            $dealer_email =  $this->config->getDealerEmail($storeId);
            $dealer_phone =  $this->config->getDealerPhone($storeId);

            //prepare custom attribute data
            $orderData['dealer_email'] =  $dealer_email;
            $orderData['is_store_pickup'] = ($order->getIsStorePickup() == 1) ? 1 : '';
            $orderData['customer_name'] = $order->getCustomerName();
            $orderData['aem_customer_account_url'] =  $this->config->getCustomerAccountUrl($storeId);
            $orderData['pickup_address'] =  $this->config->getDealerAddress($storeId);
            $orderData['email_phone_check'] = isset($dealer_email) || isset($dealer_phone) ? 1 : '';

            $creditmemo = $transportObject->getData('creditmemo');
            $creditmemoId =  $creditmemo->getId();
            $creditmemoData = $this->creditmemoRepository->get($creditmemoId);
            $refundedAmnt = $creditmemoData->getGrandTotal();

            $orderData['refunded_amount'] = $this->priceHelper->currency($refundedAmnt, true, false);

            if (!empty($dealer_email) && is_string($dealer_email)) {
                $this->transport->setReplyTo($dealer_email);
            } else {
                $this->logger->error("Please Configure the dealer email for the Store ID :- " . $storeId);
            }
            $transportObject->setData('order_data', $orderData);
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
}
