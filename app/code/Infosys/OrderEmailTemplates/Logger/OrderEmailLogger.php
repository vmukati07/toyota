<?php

/**
 * @package   Infosys/OrderEmailTemplates
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\OrderEmailTemplates\Logger;

use Magento\Framework\App\Config\ScopeConfigInterface;

class OrderEmailLogger extends \Monolog\Logger
{
	protected ScopeConfigInterface $scopeConfig;

    /**
     * @param string             $name       The logging channel
     * @param ScopeConfigInterface $scopeConfig     Scope Config class
     * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[]         $processors Optional array of processors
     */
    public function __construct(
        $name,
        ScopeConfigInterface $scopeConfig,
        array $handlers = array(),
        array $processors = array())
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors);
    }

    public function isLogEnabled()
    {
        return $this->scopeConfig->isSetFlag('orderemail_config/oetemplate_config/enable');
    }
	
    public function info($message, array $context = array())
    {
        if ($this->isLogEnabled()) {
            parent::info($message, $context);
        }

    }
}
