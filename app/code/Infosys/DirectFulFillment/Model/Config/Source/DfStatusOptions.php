<?php

/**
 * @package Infosys/DirectFulFillment
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DirectFulFillment\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class to get order direct fulfillment status options
 */
class DfStatusOptions implements OptionSourceInterface
{
    /**
     * Method to get order direct fulfillment status options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $typesOfStatus = [
            1 => 'YES',
            0 => 'NO'
        ];
        $options = [];
        foreach ($typesOfStatus as $key => $typeOfStatus) {
            $options[] = [
                'label' => $typeOfStatus,
                'value' => $key
            ];
        }
        return $options;
    }
}
