<?php
/**
 * @package   Infosys/CustomerCentral
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\CustomerCentral\Controller\Adminhtml\Order;

use Infosys\CustomerCentral\Api\CustomerCentralInterface;
use Infosys\CustomerCentral\Cron\CustomerCentralOrderQueue;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Responsible for attempting a Customer Central submission for the Order
 */
class CustomerCentralSubmit extends Action
{
	/** @var CustomerCentralInterface */
	private CustomerCentralInterface $customerCentral;

	/** @var CustomerCentralOrderQueue */
	private CustomerCentralOrderQueue $customerCentralOrderQueue;

	/** @var CustomerRepositoryInterface */
	private CustomerRepositoryInterface $customerRepository;

	/** @var OrderRepositoryInterface */
	private OrderRepositoryInterface $orderRepositoryInterface;

	/**
	 * @param Context $context
	 * @param CustomerCentralInterface $customerCentral
	 * @param CustomerCentralOrderQueue $customerCentralOrderQueue
	 * @param OrderRepositoryInterface $orderRepository
	 * @param CustomerRepositoryInterface $customerRepository
	 */
	public function __construct(
		Context $context,
		CustomerCentralInterface $customerCentral,
		CustomerCentralOrderQueue $customerCentralOrderQueue,
		OrderRepositoryInterface $orderRepository,
		CustomerRepositoryInterface $customerRepository
	) {
		$this->customerCentral = $customerCentral;
		$this->customerCentralOrderQueue = $customerCentralOrderQueue;
		$this->orderRepositoryInterface = $orderRepository;
		$this->customerRepository = $customerRepository;

		parent::__construct($context);
	}

	/**
	 * Attempt a Customer Central submission for the Order
	 *
	 * @return ResponseInterface|ResultInterface|void
	 * @throws LocalizedException
	 * @throws NoSuchEntityException
	 */
	public function execute()
	{
		$customerSubmit = (int) $this->getRequest()->getParam('submit_customer');

		$orderId = (int) $this->getRequest()->getParam('order_id');
		$order = $this->orderRepositoryInterface->get($orderId);

		if ($customerSubmit) {
			$response = '';

			if (!$order->getCustomerId()) {
				$customerData = new DataObject([]);

				try {
					$customer = $this->customerRepository->get($order->getCustomerEmail());

					// Generate a data object of the form CustomerCentral is expecting
					$customerData->setFirstName($customer->getFirstName());
					$customerData->setLastName($customer->getLastName());
					$customerData->setEmail($customer->getEmail());
					$customerData->setTelephoneNumber($customer->getAddresses()[0]->getTelephone());

				} catch (NoSuchEntityException $e) {
					// Guest checkout; read information from order's address (shipping)
					$shippingAddress = $order->getShippingAddress();

					// Generate a data object of the form CustomerCentral is expecting
					$customerData->setFirstName($shippingAddress->getFirstName());
					$customerData->setLastName($shippingAddress->getLastName());
					$customerData->setEmail($order->getCustomerEmail());
					$customerData->setTelephoneNumber($shippingAddress->getTelephone());
				}

				$response = $this->customerCentral->syncGuestCustomerInCheckout($customerData);

			} else {
				$customer = $this->customerRepository->getById($order->getCustomerId());
				$response = $this->customerCentral->syncCustomerOnUpdate($customer);
			}

			// Check response for customerCentralId to determine if request was a success or not
			if (!$response['customerCentralId']) {
				$this->messageManager->addErrorMessage(
					"No customerCentralId in response. Check logs for details."
				);
			} else {
				$this->messageManager->addSuccessMessage("Customer sync was successful");
			}

		}

		$result = $this->customerCentralOrderQueue->orderSync($order, $orderId);

		if ($this->customerCentralOrderQueue->isMessageSuccess($result)) {
			$this->messageManager->addSuccessMessage($result);
		} else {
			$this->messageManager->addErrorMessage($result);
		}

		return $this->resultRedirectFactory->create()->setPath(
			'sales/order/view',
			[
				'order_id' => $order->getEntityId()
			]
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool
	 */
	protected function _isAllowed(): bool
	{
		return $this->_authorization->isAllowed('Infosys_CustomerCentral::customer_sync');
	}
}
