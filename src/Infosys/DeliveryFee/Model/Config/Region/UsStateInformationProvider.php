<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model\Config\Region;

use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Responsible for returning an array of the Regions for US states
 */
class UsStateInformationProvider
{
	public const COUNTRY_CODE_US = 'US';

	/** @var CountryInformationAcquirerInterface */
	private CountryInformationAcquirerInterface $countryInformationAcquirer;

	/**
	 * @param CountryInformationAcquirerInterface $countryInformationAcquirer
	 */
	public function __construct(
		CountryInformationAcquirerInterface $countryInformationAcquirer
	) {
		$this->countryInformationAcquirer = $countryInformationAcquirer;
	}

	/**
	 * Return an array of all US states' code and name
	 *
	 * @return array
	 * @throws NoSuchEntityException
	 */
	public function toOptionArray()
	{
		$us = $this->countryInformationAcquirer->getCountryInfo(self::COUNTRY_CODE_US);

		$states = [];
		if ($availableStates = $us->getAvailableRegions()) {
			foreach ($availableStates as $state) {
				$states[] = [
					'value' => $state->getCode(),
					'label' => $state->getName(),
				];
			}
		}

		return $states;
	}
}
