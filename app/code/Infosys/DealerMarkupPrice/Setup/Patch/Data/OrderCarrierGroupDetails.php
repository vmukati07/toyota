<?php

/**
 * @package     Infosys/DealerMarkupPrice
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DealerMarkupPrice\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;

/**
 * Class to update carrier group details for store pickup and flatrate orders
 */
class OrderCarrierGroupDetails implements DataPatchInterface
{
    public const TABLE_NAME = 'shipperhq_order_detail_grid';

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var OrderCollectionFactory
     */
    protected OrderCollectionFactory $collectionFactory;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * Constructor function
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param OrderCollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param State $appState
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        OrderCollectionFactory $collectionFactory,
        ResourceConnection $resource,
        LoggerInterface $logger,
        State $appState
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * Patch to update carrier group details for store pickup and flatrate orders
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();
        $this->appState->setAreaCode(Area::AREA_FRONTEND);
        $data = [
            'carrier_group' => null,
            'dispatch_date' => null,
            'delivery_date' => null,
            'time_slot' => null,
            'pickup_location' => null,
            'delivery_comments' => null,
            'destination_type' => null,
            'liftgate_required' => null,
            'notify_required' => null,
            'inside_delivery' => null,
            'address_valid' => null,
            'carrier_type' => null
        ];
        try {
            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName(self::TABLE_NAME);
            // Get order collection
            $ordersCollection = $this->collectionFactory->create()
                ->addAttributeToSelect('entity_id')
                ->addAttributeToFilter('shipping_method', ['in' => ['dealerstore_pickup', 'flatrate_flatrate']]);
            $ordersCollection = $ordersCollection->getSelect()->limit(10000)->order('entity_id DESC');

            $orders = $ordersCollection->getColumnValues('entity_id');
            if (count($orders) > 0) {
                $connection->update(
                    $tableName,
                    $data,
                    ['order_id IN (?)' => $orders]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error("Error when updating order carrier group details: " . $e);
        }
        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
