<?php
/**
 * @package     Infosys/YMMSearchGraphQL
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\YMMSearchGraphQL\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class YmmSearchCache extends TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    public const TYPE_IDENTIFIER = 'efc_cache';

    /**
     * The tag name that that limits the cache cleaning scope within a particular tag
     */
    public const CACHE_TAG = 'EFC_CACHE';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
