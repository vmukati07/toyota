<?php
/**
 * @package     Infosys/DeliveryFee
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model;

use Infosys\DeliveryFee\Block\Adminhtml\Form\Field\UsStateFees;

/**
 * Return a value object array representing the configured delivery fee for the given state
 */
class GetStateDeliveryFee
{
	/** @var Configuration */
	private Configuration $configuration;

	/**
	 * @param Configuration $configuration
	 */
	public function __construct(
		Configuration $configuration
	) {
		$this->configuration = $configuration;
	}

	/**
	 * @param string|null $stateCode
	 * @return array|null
	 */
	public function execute(?string $stateCode): ?array
	{
		$stateFees = $this->configuration->getAllStateFees();

		foreach($stateFees as $stateFee) {
			if ($stateCode === $stateFee[UsStateFees::STATE_CODE]) {
				return [
					UsStateFees::STATE_CODE => $stateFee[UsStateFees::STATE_CODE],
					UsStateFees::FEE => floatval($stateFee[UsStateFees::FEE])
				];
			}
		}

		return null;
	}
}
