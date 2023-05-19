<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model\Config\Source;

use Infosys\DeliveryFee\Model\Config\Region\UsStateInformationProvider;
use Infosys\DeliveryFee\Model\Configuration;
use Magento\Directory\Model\Region;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Responsible for returning an option array of states that have an associated delivery fee set in the global config
 */
class AvailableStateFeesOptions implements OptionSourceInterface
{
	/** @var Configuration */
	private Configuration $configuration;

	/** @var Region */
	private Region $region;

	/**
	 * @param Configuration $configuration
	 * @param Region $region
	 */
	public function __construct(
		Configuration $configuration,
		Region $region
	) {
		$this->configuration = $configuration;
		$this->region = $region;
	}

	/**
	 * Return an array of all the state codes that have associated delivery fees
	 *
	 * @return array
	 */
	public function toOptionArray(): array
	{
		$stateFees = $this->configuration->getAllStateFees();

		$result = [];

		foreach ($stateFees as $stateFee) {
			$stateRegion = $this->region->loadByCode(
				$stateFee['state_code'],
				UsStateInformationProvider::COUNTRY_CODE_US
			);

			$result[] = [
				'label' => $stateRegion->getName(),
				'value' => $stateRegion->getCode()
			];
		}

		return $result;
	}
}
