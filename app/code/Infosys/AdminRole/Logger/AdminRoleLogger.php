<?php

/**
 * @package   Infosys/AdminRole
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AdminRole\Logger;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Monolog\Logger;

/**
 * Logger class for Admin Role logs
 */
class AdminRoleLogger extends Logger
{
    protected ScopeConfigInterface $scopeConfig;

    const XML_LOG_ENABLED = 'pitbulk_saml2_admin/debug/enable_logging';

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

    public function isLogEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_LOG_ENABLED);
    }

    /**
     * Admin Role logger info
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = []): void
    {
        if ($this->isLogEnabled()) {
            parent::info($message, $context);
        }
    }
}
