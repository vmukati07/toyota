<?php

/**
 * @package     Infosys/VehicleSearch
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\VehicleSearch\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Store\Model\StoreManagerInterface;
use Infosys\VehicleSearch\Model\Config\Configuration;

class VehicleIndexer implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    private Configuration $config;

    /**
     * Constructor function
     *
     * @param ConfigProvider $configProvider
     * @param StoreManagerInterface $storeManager
     * @param Configuration $config
     */
    public function __construct(
        ConfigProvider $configProvider,
        StoreManagerInterface $storeManager,
        Configuration $config
    ) {
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * Reindex Vehicle
     *
     * @param array $ids
     * @return void
     */
    public function reindex($ids = null): void
    {
        //Reindex main website
        $storeId = (int)$this->config->getStoreWebsite();
        $this->configProvider->getAdapter()->reindex((int) $storeId, $ids);
    }
    /**
     * Reindex full data
     *
     * @return void
     */
    public function executeFull(): void
    {
        $this->reindex();
    }
    /**
     * Reindex list data
     *
     * @param array $ids
     * @return void
     */
    public function executeList(array $ids): void
    {
        $this->reindex($ids);
    }
    /**
     * Reindex single data
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id): void
    {
        $this->reindex([$id]);
    }
    /**
     * Reindex array data
     *
     * @param array $ids
     * @return void
     */
    public function execute($ids): void
    {
        $this->reindex($ids);
    }
}
