<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model\CreditMemo;

use Infosys\DeliveryFee\Model\Configuration;
use Infosys\DeliveryFee\Model\DeliveryFeeRegistry;
use Magento\Sales\Model\Order\CreditMemo;
use Magento\Sales\Model\Order\CreditMemo\Total\AbstractTotal;

/**
 * Responsible for adding the delivery fee to creditmemo totals
 */
class DeliveryFeeCharge extends AbstractTotal
{
	/** @var Configuration */
	private Configuration $configuration;

	/** @var DeliveryFeeRegistry */
	private DeliveryFeeRegistry $deliveryFeeRegistry;

	/**
	 * @param DeliveryFeeRegistry $deliveryFeeRegistry
	 * @param Configuration $configuration
	 * @param array $data
	 */
	public function __construct(
		DeliveryFeeRegistry $deliveryFeeRegistry,
		Configuration $configuration,
		array $data = []
	) {
		parent::__construct($data);

		$this->configuration = $configuration;
		$this->deliveryFeeRegistry = $deliveryFeeRegistry;
	}

	/**
	 * Determine the delivery fee to charge, based on:
	 * 1) Order's assigned delivery fee
	 * 2) Creditmemo's assigned delivery fee
	 * 3) CreditMemoRegistry's stored delivery fee
	 *
	 * @param CreditMemo $creditmemo
	 * @return $this|DeliveryFeeCharge
	 */
	public function collect(
		Creditmemo $creditmemo
	) {
		// If global and store-specific refunds are not enabled, quickly return (without adding Delivery Fee to totals)
		if (!$this->configuration->isDeliveryFeeEligibleForReturns($creditmemo->getStoreId())) {
			return $this;
		}

		// Check order to see if a delivery fee was applied to the Order
		$amount = $creditmemo->getOrder()->getDeliveryFee();

		// If not, return
		if ($amount == 0) {
			return $this;
		}

		// If the creditmemo has a delivery fee assigned, use that amount
		if ($creditmemo->getDeliveryFee()) {
			$amount = $creditmemo->getDeliveryFee();
		}

		// If the DeliveryFeeRegistry has a delivery fee assigned, use that amount
		// This will be the case if form data was passed to CreditmemoFactory,
		// and was caught by Plugin/CreditMemoFactory
		if ($this->deliveryFeeRegistry->getDeliveryFee()) {
			$amount = $this->deliveryFeeRegistry->getDeliveryFee();
		}

		$creditmemo->setDeliveryFee($amount);
		$creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $amount);
		$creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $amount);

		return $this;
	}
}
