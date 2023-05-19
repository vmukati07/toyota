<?php

/**
 * @package     Infosys/ShippingRestriction
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\ShippingRestriction\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Identity for resolved shipping restriction
 */
class Identity implements IdentityInterface
{
    /**
     * shipping restriction cache tag
     */
    const CACHE_TAG = 'ship_rst';

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
        $items = $resolvedData['items'] ?? [];
        foreach ($items as $item) {
            $ids[] = sprintf('%s_%s', $this->cacheTag, $item['id']);
        }
        if (!empty($ids)) {
            array_unshift($ids, $this->cacheTag);
        }
        return $ids;
    }
}
