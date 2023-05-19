<?php

/**
 * @package     Infosys/VehicleSearch
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\VehicleSearch\Model\Config;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Configurations for Vehicle Indexer
 */
class Configuration
{
    protected StoreManagerInterface $storeManager;

    /**
     * Initialize dependencies
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * Get Default store Id
     *
     * @return int
     */
    public function getStoreWebsite(): int
    {
        $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        return (int)$storeId;
    }
}
