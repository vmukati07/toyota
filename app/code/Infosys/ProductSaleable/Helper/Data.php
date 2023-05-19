<?php

/**
 * @package Infosys/ProductSaleable
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ProductSaleable\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PRICE_THRESHOLD = 'threshold_price_config/threshold_price_group/threshold_price';

    const PRODUCT_STOCK_STATUS = 'stock_status_config/stock_status_group/stock_status';

    const AAP_PRODUCT_STATUS = 'epcconnect_hideaap/epcconnect_hide_aap/hide_aap_products';

    /**
     * Method to get product threshold price
     */
    public function getThresholdPrice(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PRICE_THRESHOLD,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get config data for a store
     *
     * @param string $config_path
     * @return string
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Method to get product stock status
     */
    public function getProductStockStatus(): string
    {
        return $this->scopeConfig->getValue(
            self::PRODUCT_STOCK_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Method to enable/disable product while import and save/update
     * checking Hide AAP Products setting First
     */
    public function getAapProductStatus(): string
    {
        return $this->scopeConfig->getValue(
            self::AAP_PRODUCT_STATUS,
            ScopeInterface::SCOPE_STORE
        );
    }
}
