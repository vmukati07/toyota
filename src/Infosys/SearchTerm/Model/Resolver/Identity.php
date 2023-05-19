<?php

/**
 * @package Infosys/SearchTerm
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\SearchTerm\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Identity for resolved search terms
 */
class Identity implements IdentityInterface
{
    /**
     * search term cache tag
     */
    const CACHE_TAG = 'search_t';

    /** @var string */
    private string $cacheTagSearchTerm = self::CACHE_TAG;

    /**
     * Get identity tags from resolved data
     *
     * @param array $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $items = $resolvedData['search_terms'] ?? [];

        foreach ($items as $item) {
            $item = preg_replace('/\s+/', '', $item);
            $ids[] = sprintf('%s_%s', $this->cacheTagSearchTerm, $item);
        }
        if (!empty($ids)) {
            array_unshift($ids, $this->cacheTagSearchTerm);
        }

        return $ids;
    }
}
