<?php
/**
 * @package     Infosys/DeliveryFee
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use ShipperHQ\Shipper\Helper\Data;

/**
 * Responsible for providing a list of ShipperHQ's carrier_method codes
 */
class GetShipperHqCarrierMethodCodes
{
	/** @var Json */
	private Json $json;

	/** @var ScopeConfigInterface */
	private ScopeConfigInterface $scopeConfig;

	/**
	 * @param ScopeConfigInterface $scopeConfig
	 */
	public function __construct(
		Json $json,
		ScopeConfigInterface $scopeConfig
	) {
		$this->json = $json;
		$this->scopeConfig = $scopeConfig;
	}

	/**
	 * Generate an array of strings of the form CARRIER_METHOD for each of the specified-by-configuration
	 * ShipperHQ delivery methods. Needed to determine if a given shipping method is originating from ShipperHQ,
	 * and if so, apply the fee.
	 *
	 * @see \Infosys\DeliveryFee\Model\Total\DeliveryFeeCharge
	 *
	 * @return array
	 */
	public function execute() : array
	{
		$allowedMethodsArray = $this->json->unserialize(
			$this->scopeConfig->getValue(Data::SHIPPERHQ_SHIPPER_ALLOWED_METHODS_PATH)
		);

		$result = [];

		foreach ($allowedMethodsArray as $carrierCode => $carrierMethods) {
			foreach ($carrierMethods as $methodCode => $methodTitle) {
				$result[] = $carrierCode . "_" . $methodCode;
			}
		}

		return $result;
	}
}
