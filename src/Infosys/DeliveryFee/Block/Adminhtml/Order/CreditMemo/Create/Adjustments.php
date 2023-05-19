<?php
/**
 * @package     Infosys/DeliveryFee
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Block\Adminhtml\Order\CreditMemo\Create;

use Infosys\DeliveryFee\Model\Configuration;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

/**
 * Responsible for managing the adjustments template and totals during CreditMemo creation
 */
class Adjustments extends Template
{
	/** @var Configuration */
	private Configuration $configuration;

	/**
	 * @param Context $context
	 * @param Configuration $configuration
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		Configuration $configuration,
		array $data = []
	) {
		parent::__construct($context, $data);

		$this->configuration = $configuration;
	}

	/**
	 * Generate delivery fee total for display of adjustments
	 *
	 * @return $this
	 */
	public function initTotals()
	{
		$parent = $this->getParentBlock();
		$creditMemo = $parent->getSource();
		$deliveryFee = $creditMemo->getDeliveryFee();

		if ($deliveryFee) {
			$total = new DataObject([
				'code' => 'delivery_fee_adjustments',
				'amount' => $creditMemo->getDeliveryFee(),
				'block_name' => $this->getNameInLayout()
			]);

			$parent->removeTotal('delivery_fee_adjustments');
			$parent->addTotal($total);
		}

		return $this;
	}

	/**
	 * Return the source
	 *
	 * @return mixed
	 */
	public function getSource()
	{
		return $this->getParentBlock()->getSource();
	}

	/**
	 * Return the label for the input
	 *
	 * @return Phrase
	 */
	public function getDeliveryFeeLabel()
	{
		return __("Delivery Fee");
	}

	/**
	 * Return boolean representing if the given store has refunds for delivery fee enabled
	 *
	 * @param $storeId
	 * @return bool
	 */
	public function isEligibleForReturn($storeId)
	{
		return $this->configuration->isDeliveryFeeEligibleForReturns($storeId);
	}
}
