<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model\Invoice;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Responsible for adding the delivery fee to invoice totals
 */
class DeliveryFeeCharge extends AbstractTotal
{
	/**
	 * If the order has a delivery fee associated with it, add the delivery fee to the total
	 *
	 * @param Invoice $invoice
	 * @return $this|DeliveryFeeCharge
	 */
	public function collect(
		Invoice $invoice
	) {
		$amount = $invoice->getOrder()->getDeliveryFee();

		if ($amount == 0) {
			return $this;
		}

		$invoice->setDeliveryFee($amount);
		$invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
		$invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $amount);

		return $this;
	}
}
