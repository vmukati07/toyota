<?php

/**
 * @package Infosys/SearchByVIN
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\SearchByVIN\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Identity for resolved search by vin
 */
class Identity implements IdentityInterface
{
    /**
     * search by vin cache tag
     */
    const CACHE_TAG = 'search_vin';

    /** @var string */
    private string $cacheTag = self::CACHE_TAG;

    /**
     * Get identity tags from resolved data
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $items = $resolvedData['allRecords'] ?? [];
        foreach ($items as $item) {
            $ids[] = sprintf('%s_%s', self::CACHE_TAG, $item['entity_id']);
        }
        if (!empty($ids)) {
            $ids[] = self::CACHE_TAG;
        }
        return $ids;
    }
}
