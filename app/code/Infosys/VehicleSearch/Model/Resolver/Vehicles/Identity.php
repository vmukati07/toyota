<?php

/**
 * @package     Infosys/Vehicle
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\VehicleSearch\Model\Resolver\Vehicles;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use Infosys\Vehicle\Model\Vehicle;

/**
 * Identity for resolved vehicles
 */
class Identity implements IdentityInterface
{
    /** @var string */
    private string $cacheTagVehicle = Vehicle::CACHE_TAG;

    /**
     * Get product ids for cache tag
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $vehicles = $resolvedData['items'] ?? [];

        foreach ($vehicles as $vehicle) {
            $ids[] = sprintf('%s_%s', $this->cacheTagVehicle, $vehicle['entity_id']);
        }
        if (!empty($ids)) {
            array_unshift($ids, $this->cacheTagVehicle);
        }

        return $ids;
    }
}
