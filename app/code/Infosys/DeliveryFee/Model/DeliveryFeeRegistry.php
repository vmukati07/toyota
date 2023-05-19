<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model;

/**
 * Provide a cache for the value of delivery_fee as it comes from the adjustment form for later use in
 *
 * @see \Infosys\DeliveryFee\Model\CreditMemo\DeliveryFeeCharge
 */
class DeliveryFeeRegistry
{
	private $deliveryFee = null;

	/**
	 * Set the delivery fee
	 *
	 * @param $deliveryFee
	 */
	public function setDeliveryFee($deliveryFee)
	{
		$this->deliveryFee = $deliveryFee;
	}

	/**
	 * Get the delivery fee
	 *
	 * @return mixed
	 */
	public function getDeliveryFee()
	{
		return $this->deliveryFee;
	}
}