<?php

/**
 * @package   Infosys/CustomerCentral
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\CustomerCentral\Cron;

use Infosys\CustomerCentral\Model\ResourceModel\CustomerCentralOrderQueue\Collection;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Infosys\CustomerCentral\Helper\Data;
use Infosys\CustomerCentral\Model\CustomerCentral;
use Infosys\CustomerCentral\Logger\CustomerCentralLogger;
use Infosys\CustomerCentral\Model\ResourceModel\CustomerCentralOrderQueue\CollectionFactory;
use Infosys\CustomerCentral\Model\CustomerCentralOrderQueueFactory;

class CustomerCentralOrderQueue
{
	private const API_MESSAGE_ALREADY_EXISTS = "Order and Shipping details already exist in database.";
	public const API_STATUS_RETRY = "Retry";
	public const API_STATUS_SUCCESS = "Success";

	/** @var LoggerInterface */
    protected $logger;

    /** @var CustomerCentral */
    protected $customerCentral;

    /** @var CustomerCentralLogger */
    protected $CCLogger;

    /** @var Data */
    protected $helper;

    /** @var OrderRepositoryInterface */
    protected $orderRepository;

    /** @var Collection  */
    protected $ccOrderQueueCollectionFactory;

	/** @var \Infosys\CustomerCentral\Model\CustomerCentralOrderQueue */
	protected $customerCentralOrderQueue;

	protected $_date;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     * @param CustomerCentral $customerCentral
     * @param CustomerCentralLogger $CCLogger
     * @param Data $helper
     * @param CollectionFactory $ccOrderQueueCollectionFactory
     * @param CustomerCentralOrderQueueFactory $saveCustomerCentralOrderQueueFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        CustomerCentral $customerCentral,
        CustomerCentralLogger $CCLogger,
        Data $helper,
        CollectionFactory $ccOrderQueueCollectionFactory,
        CustomerCentralOrderQueueFactory $saveCustomerCentralOrderQueueFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->customerCentral = $customerCentral;
        $this->CCLogger = $CCLogger;
        $this->helper = $helper;
        $this->ccOrderQueueCollectionFactory = $ccOrderQueueCollectionFactory->create();
        $this->customerCentralOrderQueue = $saveCustomerCentralOrderQueueFactory->create();
    }
    
    /**
     * Order Sync Execute function
     *
     * @return $this
     */
    public function execute()
    {
        if ($this->helper->isLogEnabled()) {
            $this->CCLogger->info('Customer Central Sync pending order cron time');
        }
        
        $retryCount = 0;
        $status = 'Pending';

        $orders = $this->ccOrderQueueCollectionFactory
            ->addFieldToSelect(['order_id','retry_count'])
            ->addFieldToFilter('retry_count', ['eq' => $retryCount])
            ->addFieldToFilter('api_status', ['eq' => $status]);
        
        foreach ($orders as $orderQueue) {
            $orderId = $orderQueue->getData('order_id');
            $order = $this->orderRepository->get($orderId);

            // We don't want to send Orders to Customer Central if they are still awaiting fraud approval (Signifyd)
	        // Omit Orders that are still in fraud review from this round of sync; state & status == "holded"
	        if ($order->getStatus() == "holded") {
	        	continue;
	        }

            $response = $this->orderSync($order, $orderId);
            if($response != ''){
                $order->addCommentToStatusHistory($response);
                $this->orderRepository->save($order);
            }
            
        }

        return $this;
    }

    /**
     * Order sync with customer central
     * @param $order
     * @param $orderId
     * @return string
     */
    public function orderSync($order, $orderId)
    {
        try {
            $response = $this->customerCentral->syncCustomerOnOrderPlace($order);
            $orderQueue = $this->customerCentralOrderQueue->load($orderId, 'order_id');
            $retryCount = $orderQueue->getData('retry_count');            
            $message = '';
            
            if (isset($response['customerId']) && $response['syncError'] == '') {
                if ($this->helper->isLogEnabled()) {
                    $this->CCLogger->info('CustomerCentralId-' . $response['customerId']);
                }
                $orderQueue->setData('api_status', self::API_STATUS_SUCCESS)
                    ->setData('messages', "Order sync cron - Order sync successfully done.")
                    ->save();
                
                if ($this->helper->isLogEnabled()) {
                    $this->CCLogger->info('Order sync cron - Order sync successfully done.');
                }

                $message = "Customer Central Notice: Order sync successfully done.";
                

            } else {
            	// If the Order / Shipping information has already been sync'd, the API returns an exception containing
	            // the text "Order and Shipping details already exist in database." In this case, we do not want to
	            // change the `api_status` - just return; if we were to save here, it would be in state 'retry'
	            if (isset($response['syncError']) && self::API_MESSAGE_ALREADY_EXISTS === $response['syncError']) {
		            if ($this->helper->isLogEnabled()) {
			            $this->CCLogger->info('Order sync cron - '. $response['syncError']);
		            }

	            	return $response['syncError'];
	            }

                $retryCount= $retryCount + 1;
                $orderQueue->setData('api_status', self::API_STATUS_RETRY)
                    ->setData('retry_count', $retryCount)
                    ->setData('messages', $response['syncError'])
                    ->save();
                
                if ($this->helper->isLogEnabled()) {
                    $this->CCLogger->info('Order sync cron - '. $response['syncError']);
                }

                $message = "Customer Central Notice: Order sync failed, details saved to logs. (" . $response['syncError'] . ")";
            }
            
            return $message;
            
        } catch (\Exception $e) {
            $this->CCLogger->error('Error when syncing the order with CC: ' . $e);
        }
    }

	/**
	 * Return a bool indicating if the provided message was successful
	 *
	 * @param string $message
	 * @return bool
	 */
    public function isMessageSuccess(string $message): bool
    {
    	return strpos($message, "failed") === false;
    }
}
