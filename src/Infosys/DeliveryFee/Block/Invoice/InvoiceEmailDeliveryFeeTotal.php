<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Block\Invoice;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Responsible for rendering any delivery fee on an amount to the invoice email template
 */
class InvoiceEmailDeliveryFeeTotal extends AbstractBlock
{
	/**
	 * Render out the delivery fee, if it exists
	 */
	public function initTotals()
	{
		$parentBlock = $this->getParentBlock();
		$order = $parentBlock->getOrder();

		if ($order->getDeliveryFee() > 0) {
			$parentBlock->addTotal(
				new DataObject([
					'code' => 'delivery_fee',
					'label' => __('%1 Delivery Fee', $order->getDeliveryFeeState()),
					'value' => $order->getDeliveryFee(),
					'base_value' => $order->getDeliveryFee()
				]),
				'subtotal'
			);
		}

		return $this;
	}
}
