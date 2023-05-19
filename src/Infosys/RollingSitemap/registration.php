<?php
/**
 * @package     Infosys/RollingSitemap
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2022. All Rights Reserved.
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
	ComponentRegistrar::MODULE,
	'Infosys_RollingSitemap',
	__DIR__
);
