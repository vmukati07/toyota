<?php

/**
 * @package   Infosys/XtentoProductExport
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Handler class for xtento product export logs
 */
class ProductExportHandler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = Logger::INFO;

    public $fileName = '/var/log/toyota_xtento_products_export.log';
}
