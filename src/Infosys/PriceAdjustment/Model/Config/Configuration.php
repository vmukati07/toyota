<?php

/**
 * @package     Infosys/PriceAdjustment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\PriceAdjustment\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Configurations for Price Adjustment
 */
class Configuration
{
    public const ENABLE_DEALER_PRICE_CALC_CRON = 'discount/discount_configuration/price_calc_cronjob';
    public const ENABLE_TIER_PRICE_IMPORT_DURING_PRODUCT_IMPORT = 'discount/discount_configuration/tier_price_import';
    public const ENABLE_DISCOUNT_LOGGING = 'discount/discount_configuration/enable_logging';
    public const UPDATE_BATCH_COUNT = 'discount/discount_configuration/maximum_product_count';
    public const SPECIAL_PRICE_UPDATE_PER_BATCH = 'discount/discount_configuration/update_price_per_batch';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Enable/Disable dealer price calculation during Product Import
     *
     * @return bool
     */
    public function enableDealerPriceCalcDuringImport(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::ENABLE_DEALER_PRICE_CALC_CRON,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Enable/Disable Tier Price Import During Product Import
     *
     * @return bool
     */
    public function enableTierPriceImportDuringImport(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::ENABLE_TIER_PRICE_IMPORT_DURING_PRODUCT_IMPORT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Convenient accessor for logging configuration
     *
     * @return bool
     */
    public function isLoggingEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::ENABLE_DISCOUNT_LOGGING);
    }

    /**
     * Convenient accessor for batch size
     *
     * @return mixed
     */
    public function getBatchCount()
    {
        return $this->scopeConfig->getValue(self::UPDATE_BATCH_COUNT);
    }

    /**
     * Getting special price update batch count
     *
     * @return mixed
     */
    public function getSpecialPriceUpdateBatch()
    {
        return (int)$this->scopeConfig->getValue(self::SPECIAL_PRICE_UPDATE_PER_BATCH);
    }
}
