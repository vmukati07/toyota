<?php

/**
 * @package   Infosys/XtentoOrderExport
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoOrderExport\Logger;

use Infosys\XtentoOrderExport\Model\Config\Configuration as Data;
use \Monolog\Logger;

/**
 * Logger class for xtento orders export logs
 */
class OrderExportLogger extends Logger
{
    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * Initialize dependencies
     *
     * @param string             $name       The logging channel
     * @param Data               $helper     Helper class
     * @param HandlerInterface[] $handlers   Optional stack of handlers
     * @param callable[]         $processors Optional array of processors
     */
    public function __construct(
        $name,
        Data $helper,
        array $handlers = [],
        array $processors = []
    ) {
        $this->helper = $helper;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Log info function
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []): void
    {
        if ($this->helper->isLogEnabled()) {
            parent::info($message, $context);
        }
    }
}
