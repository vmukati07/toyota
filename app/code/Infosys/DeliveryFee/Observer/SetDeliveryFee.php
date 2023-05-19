<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Observer;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Copy needed fields from order to quote
 *
 * Note that this shouldn't be needed, but `copyFieldsetToTarget` from core doesn't work
 */
class SetDeliveryFee implements ObserverInterface
{
	/** @var Copy */
	private Copy $copy;

	/**
	 * @param Copy $copy
	 */
	public function __construct(
		Copy $copy
	) {
		$this->copy = $copy;
	}

	/**
	 * @param Observer $observer
	 * @return $this|void
	 */
	public function execute(Observer $observer)
	{
		$quote = $observer->getEvent()->getDataByKey('quote');
		$order = $observer->getEvent()->getDataByKey('order');

		// copyFieldsetToTarget isn't working from core - so perform this manually
		//$this->copy->copyFieldsetToTarget('sales_convert_quote', 'to_order', $quote, $order);
		$order->setDeliveryFee($quote->getDeliveryFee());
		$order->setDeliveryFeeState($quote->getDeliveryFeeState());

		return $this;
	}
}
