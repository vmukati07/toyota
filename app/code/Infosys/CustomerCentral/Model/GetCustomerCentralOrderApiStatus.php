<?php
/**
 * @package   Infosys/CustomerCentral
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\CustomerCentral\Model;

/**
 * Responsible for determining if a given Order has been successfully submitted to Customer Central or not
 */
class GetCustomerCentralOrderApiStatus
{
	/** @var CustomerCentralOrderQueueFactory */
	private CustomerCentralOrderQueueFactory $factory;

	/**
	 * @param CustomerCentralOrderQueueFactory $factory
	 */
	public function __construct(
		CustomerCentralOrderQueueFactory $factory
	) {
		$this->factory = $factory;
	}

	/**
	 * Return true if the order queue record with order_id == $orderId has been successfully submitted, false otherwise
	 *
	 * @param int $orderId
	 * @return bool
	 */
	public function execute(int $orderId): bool
	{
		$orderQueue = $this->factory->create();
		$orderQueueRecord = $orderQueue->load($orderId, 'order_id');

		return $orderQueueRecord->getApiStatus() ==
			\Infosys\CustomerCentral\Cron\CustomerCentralOrderQueue::API_STATUS_SUCCESS;
	}
}
