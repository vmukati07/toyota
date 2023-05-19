<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Block\Adminhtml\Order\View;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;

/**
 * Responsible for adding any Delivery Fee to Order totals when viewing an existing Order
 */
class DeliveryFeeCharge extends Template
{
	/**
	 * Generate delivery fee total for display
	 *
	 * @return $this
	 */
	public function initTotals()
	{
		$parent = $this->getParentBlock();
		$order = $parent->getOrder();
		$deliveryFee = $order->getDeliveryFee();

		$fee = new DataObject([
			'code' => 'delivery_fee',
			'strong' => false,
			'value' => $deliveryFee,
			'label' => __('Delivery Fee')
		]);

		if ($deliveryFee != "null" && $deliveryFee != 0) {
			$parent->addTotal($fee, 'delivery_fee');
		}

		return $this;
	}
}
