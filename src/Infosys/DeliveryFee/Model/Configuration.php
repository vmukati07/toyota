<?php
/**
 * @package Infosys/DeliveryFee
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DeliveryFee\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Responsible for providing methods that allow easy access to this module's configuration values
 */
class Configuration
{
	public const STORES_SCOPE = 'stores';
	public const XML_PATH_GLOBAL_ENABLE = 'delivery_fee_global/global/enabled';
	public const XML_PATH_GLOBAL_STATE_FEES = 'delivery_fee_global/global/state_fees';
	public const XML_PATH_STORE_ENABLED = 'delivery_fee_website/website/enabled';
	public const XML_PATH_STORE_ENABLED_STATES = 'delivery_fee_website/website/enabled_states';
	public const XML_PATH_LOGGING_ENABLED = 'delivery_fee_global/global/logging_enabled';
	public const AVAILABLE_FOR_RETURNS = 'delivery_fee_global/global/available_for_returns';
	public const AVAILABLE_FOR_RETURNS_STORE = 'delivery_fee_website/website/available_for_returns_store';

	/** @var Json */
	private Json $json;

	/** @var ScopeConfigInterface */
	private ScopeConfigInterface $scopeConfig;

	/**
	 * @param Json $json
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
	 * Return a boolean indicating if the module is enabled, globally
	 *
	 * @return bool
	 */
	public function isEnabledGlobally(): bool
	{
		return $this->scopeConfig->isSetFlag(self::XML_PATH_GLOBAL_ENABLE);
	}

	/**
	 * Return the enabled status for a particular store
	 *
	 * @param $storeId
	 * @return bool
	 */
	public function isEnabledForStore($storeId): bool
	{
		return $this->scopeConfig->isSetFlag(
			self::XML_PATH_STORE_ENABLED,
			self::STORES_SCOPE,
			$storeId
		);
	}

	/**
	 * Return the globally configured state fees
	 *
	 * @return array
	 */
	public function getAllStateFees(): array
	{
		return $this->json->unserialize($this->scopeConfig->getValue(self::XML_PATH_GLOBAL_STATE_FEES));
	}

	/**
	 * For the given website id, return an array of state codes that have delivery fee enabled for that website
	 *
	 * @param $storeId
	 * @return array
	 */
	public function getEnabledStateCodesByStoreId($storeId): array
	{
		$enabledStores = $this->scopeConfig->getValue(
			self::XML_PATH_STORE_ENABLED_STATES,
			self::STORES_SCOPE,
			$storeId
		);

		if (!$enabledStores) {
			return [];
		}

		return explode(
			',',
			$enabledStores
		);
	}

	/**
	 * Return a bool if both global and store return eligibility is true
	 *
	 * @param $storeId
	 * @return bool
	 */
	public function isDeliveryFeeEligibleForReturns($storeId): bool
	{
		$enabledGlobal = $this->scopeConfig->isSetFlag(
			self::AVAILABLE_FOR_RETURNS
		);

		$enabledStore = $this->scopeConfig->isSetFlag(
			self::AVAILABLE_FOR_RETURNS_STORE,
			self::STORES_SCOPE,
			$storeId
		);

		return $enabledGlobal && $enabledStore;
	}

	/**
	 * Return a boolean indicating if logging is enabled for the DeliveryFee module or not
	 *
	 * @return bool
	 */
	public function isLoggingEnabled(): bool
	{
		return $this->scopeConfig->isSetFlag(self::XML_PATH_LOGGING_ENABLED);
	}
}
