<?php

/**
 * @package   Infosys/ShipStationOrders
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ShipStationOrders\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Handler class to handle shipstation orders logs
 */
class ShipStationOrdersHandler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    public $fileName = '/var/log/toyota_shipstation_orders.log';
}
