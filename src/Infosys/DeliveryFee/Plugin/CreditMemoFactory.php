<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Plugin;

use Infosys\DeliveryFee\Model\DeliveryFeeRegistry;
use \Magento\Sales\Model\Order\CreditmemoFactory as BaseCreditmemoFactory;

/**
 * Responsible for storing the delivery_fee_adjustments data in a registry for use by plugins
 *
 * @see \Infosys\DeliveryFee\Model\CreditMemo\DeliveryFeeCharge
 * @see \Magento\Sales\Model\Order\CreditmemoFactory
 */
class CreditMemoFactory
{
	/** @var DeliveryFeeRegistry */
	private DeliveryFeeRegistry $deliveryFeeRegistry;

	/**
	 * @param DeliveryFeeRegistry $deliveryFeeRegistry
	 */
	public function __construct(DeliveryFeeRegistry $deliveryFeeRegistry)
	{
		$this->deliveryFeeRegistry = $deliveryFeeRegistry;
	}

	/**
	 * If `delivery_fee_adjustments` is set, then store the value in a registry for use in
	 * Infosys\DeliveryFee\Model\CreditMemo collect()
	 *
	 * @param BaseCreditmemoFactory $subject
	 * @param $invoice
	 * @param array $data
	 * @return mixed
	 */
	public function beforeCreateByOrder(
		BaseCreditmemoFactory $subject,
		$invoice,
		array $data
	) {
		if (isset($data['delivery_fee_adjustments'])) {
			$this->deliveryFeeRegistry->setDeliveryFee($data['delivery_fee_adjustments']);
		}

		return [$invoice, $data];
	}

	/**
	 * If `delivery_fee_adjustments` is set, then store the value in a registry for use in
	 * Infosys\DeliveryFee\Model\CreditMemo collect()
	 *
	 * @param BaseCreditmemoFactory $subject
	 * @param $order
	 * @param array $data
	 * @return mixed
	 */
	public function beforeCreateByInvoice(
		BaseCreditmemoFactory $subject,
		$order,
		array $data
	) {
		if (isset($data['delivery_fee_adjustments'])) {
			$this->deliveryFeeRegistry->setDeliveryFee($data['delivery_fee_adjustments']);
		}

		return [$order, $data];
	}
}
