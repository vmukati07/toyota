<?php

/**
 * @package Infosys/XtentoOrderExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class to get xtento store configuration values
 */
class Configuration extends AbstractModel
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    const XML_LOG_ENABLED = 'orders_export_xtento/logging_errors/active';

    /**
     * Constructor function
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Log enabled/disabled function
     *
     * @return string
     */
    public function isLogEnabled(): string
    {
        $isEnabled = $this->scopeConfig->getValue(
            self::XML_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        return $isEnabled;
    }
}
