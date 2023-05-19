<?php

/**
 * @package   Infosys/AdminRole
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AdminRole\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Handler class for Admin Role logs
 */
class AdminRoleHandler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * Log file
     *
     * @var string
     */
    public $fileName = '/var/log/dealer_login.log';
}
