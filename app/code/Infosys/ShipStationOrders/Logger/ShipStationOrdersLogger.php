<?php

/**
 * @package   Infosys/ShipStationOrders
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ShipStationOrders\Logger;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Monolog\Logger;

/**
 * Logger class to handle shipstation orders logs
 */
class ShipStationOrdersLogger extends Logger
{
    const XML_LOG_ENABLED = 'shipstation_general/logging_errors/active';

    protected ScopeConfigInterface $scopeConfig;

    /**
     * Constructor function
     *
     * @param string $name
     * @param ScopeConfigInterface $scopeConfig
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        $name,
        ScopeConfigInterface $scopeConfig,
        array $handlers = [],
        array $processors = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Log enabled/disbled function
     *
     * @return bool
     */
    public function isLogEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_LOG_ENABLED);
    }

    /**
     * Log info function
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []): void
    {
        if ($this->isLogEnabled()) {
            parent::info($message, $context);
        }
    }
}
