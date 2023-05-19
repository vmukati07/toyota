<?php
/**
 * @package     Infosys/DeliveryFee
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model\Total;

use Infosys\DeliveryFee\Block\Adminhtml\Form\Field\UsStateFees;
use Infosys\DeliveryFee\Model\Configuration;
use Infosys\DeliveryFee\Model\GetShipperHqCarrierMethodCodes;
use Infosys\DeliveryFee\Model\GetStateDeliveryFee;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Shipping\Model\Config;
use Psr\Log\LoggerInterface;

/**
 * Total for the DeliveryFee module's assessed charge to be represented with
 */
class DeliveryFeeCharge extends AbstractTotal
{
	public const BASE_STATE_DELIVERY_FEE_CODE = 'base_state_delivery_fee';
	public const STATE_DELIVERY_FEE_CODE = 'state_delivery_fee';
	public const STATE_DELIVERY_FEE_TITLE = 'Delivery Fee';

	/** @var Config */
	private Config $shippingConfig;

	/** @var Configuration */
	private Configuration $configuration;

	/** @var GetStateDeliveryFee */
	private GetStateDeliveryFee $getStateDeliveryFee;

	/** @var GetShipperHqCarrierMethodCodes */
	private GetShipperHqCarrierMethodCodes $getShipperHqCarrierMethodCodes;

	/** @var LoggerInterface */
	private LoggerInterface $logger;

	/**
	 * @param Config $shippingConfig
	 * @param Configuration $configuration
	 * @param GetStateDeliveryFee $getStateDeliveryFee
	 * @param GetShipperHqCarrierMethodCodes $getShipperHqCarrierMethodCodes
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		Config $shippingConfig,
		Configuration $configuration,
		GetStateDeliveryFee $getStateDeliveryFee,
		GetShipperHqCarrierMethodCodes $getShipperHqCarrierMethodCodes,
		LoggerInterface $logger
	) {
		$this->shippingConfig = $shippingConfig;
		$this->configuration = $configuration;
		$this->getStateDeliveryFee = $getStateDeliveryFee;
		$this->getShipperHqCarrierMethodCodes = $getShipperHqCarrierMethodCodes;
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 *
	 * @param Total $total
	 */
	protected function clearValues(
		Total $total
	) {
		$total->setTotalAmount(self::STATE_DELIVERY_FEE_CODE, 0);
		$total->setBaseTotalAmount(self::BASE_STATE_DELIVERY_FEE_CODE, 0);
		$total->setStateDeliveryFeeCharge(0);
		$total->setBaseStateDeliveryFeeCharge(0);
		$total->setGrandTotal(0);
		$total->setBaseGrandTotal(0);
	}

	/**
	 * @inheritDoc
	 *
	 * @param Quote $quote
	 * @param ShippingAssignmentInterface $shippingAssignment
	 * @param Total $total
	 * @return $this|DeliveryFeeCharge
	 * @throws \Exception
	 */
	public function collect(
		Quote $quote,
		ShippingAssignmentInterface $shippingAssignment,
		Total $total
	) {
		parent::collect($quote, $shippingAssignment, $total);

		if (
			$this->configuration->isEnabledGlobally() &&
			$this->configuration->isEnabledForStore($quote->getStoreId())
		) {
			$this->logIfEnabled(
				sprintf("Total/DeliveryFeeCharge::collect() called for quote %s", $quote->getId())
			);

			$shippingAddress = $shippingAssignment->getShipping()->getAddress();
			$selectedMethod = $shippingAssignment->getShipping()->getMethod();
			$shqCodes = $this->getShipperHqCarrierMethodCodes->execute();

			// Check to ensure a method is selected, and that there are at least some ShipperHQ codes
			if (!$selectedMethod || !$shqCodes) {
				$this->logIfEnabled(
					sprintf("No method or no SHQ codes applicable for quote %s", $quote->getId())
				);

				return $this;
			}

			// Check if the selected method is a ShipperHQ code
			if (!in_array($selectedMethod, $shqCodes)) {
				$this->logIfEnabled(
					sprintf("Selected method not an SHQ match for quote %s", $quote->getId())
				);

				$this->removeDeliveryFee($quote);
				return $this;
			}

			// Check if the address' state is in the enabled states for the store
			if (!in_array(
				$shippingAddress->getRegionCode(),
				$this->configuration->getEnabledStateCodesByStoreId($quote->getStoreId())
			)) {
				$this->logIfEnabled(
					sprintf("State (region) not applicable for quote %s", $quote->getId())
				);

				$this->removeDeliveryFee($quote);
				return $this;
			}

			$stateFee = $this->getStateDeliveryFee->execute($shippingAddress->getRegionCode());

			// Check that there is a value for a state delivery fee to apply
			if (!$stateFee) {
				$this->logIfEnabled(
					sprintf("No applicable state fee for quote %s", $quote->getId())
				);

				$this->removeDeliveryFee($quote);
				return $this;
			}

			$this->logIfEnabled(
				sprintf("Applying DeliveryFee to quote %s", $quote->getId())
			);

			$quote->setDeliveryFee($stateFee[UsStateFees::FEE]);
			$quote->setDeliveryFeeState($shippingAddress->getRegionCode());

			$total->setStateDeliveryFeeCharge($stateFee[UsStateFees::FEE]);
			$total->setBaseStateDeliveryFeeCharge($stateFee[UsStateFees::FEE]);
			$total->addTotalAmount(self::STATE_DELIVERY_FEE_CODE, $stateFee[UsStateFees::FEE]);
			$total->addBaseTotalAmount(self::BASE_STATE_DELIVERY_FEE_CODE, $stateFee[UsStateFees::FEE]);
		}

		return $this;
	}

	/**
	 * @inheritDoc
	 *
	 * @param Quote $quote
	 * @param Total $total
	 * @return array
	 */
	public function fetch(
		Quote $quote,
		Total $total
	) {
		$this->logIfEnabled(
			sprintf("Total/DeliveryFeeCharge::fetch() called for quote %s", $quote->getId())
		);

		if (
			!$this->configuration->isEnabledGlobally() ||
			!$this->configuration->isEnabledForStore($quote->getStoreId())
		) {
			$this->logIfEnabled(
				sprintf("Failed enabled check for quote %s", $quote->getId())
			);

			return null;
		}

		if (!in_array(
			$quote->getShippingAddress()->getRegionCode(),
			$this->configuration->getEnabledStateCodesByStoreId($quote->getStoreId())
		)) {
			$this->logIfEnabled(
				sprintf("Failed enabled state check for quote %s", $quote->getId())
			);

			return null;
		}

		$stateFee = $this->getStateDeliveryFee->execute($quote->getShippingAddress()->getRegionCode());

		if (!$stateFee) {
			$this->logIfEnabled(
				sprintf("Failed delivery fee exists check for quote %s", $quote->getId())
			);

			return null;
		}

		$result = [
			'code' => self::STATE_DELIVERY_FEE_CODE,
			'title' => self::STATE_DELIVERY_FEE_TITLE,
			'value' => $quote->getDeliveryFee(),
			'base_value' => $quote->getDeliveryFee()
		];

		$this->logIfEnabled(
			sprintf("Total/DeliveryFeeCharge::fetch() returning for quote %s", $quote->getId()),
			$result
		);

		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @return Phrase|string
	 */
	public function getLabel()
	{
		return __(self::STATE_DELIVERY_FEE_TITLE);
	}

	/**
	 * Removes a delivery fee from the given quote (if it has one), and persists the quote
	 *
	 * @param $quote
	 */
	private function removeDeliveryFee($quote): void
	{
		if ($quote->getDeliveryFee()) {
			$this->logIfEnabled(
				sprintf("Removing DeliveryFee from quote %s; causes a save", $quote->getId()),
			);

			$quote->setDeliveryFee(0);
			$quote->setDeliveryFeeState(null);
		}
	}

	/**
	 * Logs the provided message and context if logging is enabled
	 *
	 * @param $message
	 * @param $context
	 */
	private function logIfEnabled($message, $context = []): void
	{
		if ($this->configuration->isLoggingEnabled()) {
			$this->logger->info($message, $context);
		}
	}
}
