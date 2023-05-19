<?php

/**
 * @package     Infosys/Reports
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\Reports\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;

/**
 * Class to update national promotional discount on orders
 */
class NationalPromotionalDiscount implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var OrderCollectionFactory
     */
    protected OrderCollectionFactory $collectionFactory;

    /**
     * @var OrderFactory
     */
    protected OrderFactory $orderFactory;

    /**
     * @var OrderResourceModel
     */
    protected OrderResourceModel $orderModel;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * Constructor function
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param OrderCollectionFactory $collectionFactory
     * @param OrderFactory $orderFactory
     * @param OrderResourceModel $orderModel
     * @param LoggerInterface $logger
     * @param State $appState
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        OrderCollectionFactory $collectionFactory,
        OrderFactory $orderFactory,
        OrderResourceModel $orderModel,
        LoggerInterface $logger,
        State $appState
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->orderFactory = $orderFactory;
        $this->orderModel = $orderModel;
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * Patch to update national promotional discount for old orders
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();
        $this->appState->setAreaCode(Area::AREA_FRONTEND);

        try {
            // Get order collection
            $ordersCollection = $this->collectionFactory->create()
                ->addAttributeToSelect('increment_id');
            $ordersCollection->getSelect()->columns(
                [
                    'total_discount' => 'ABS(base_discount_amount)',
                    'shipping_discount' => 'shipping_discount_amount'
                ]
            )
                ->where('ABS(base_discount_amount) > (?)', 0)
                ->where('ABS(base_discount_amount) != shipping_discount_amount');
            $ordersCollection->getSelect()->limit(10000)->order('entity_id DESC');
                
            $orders = $ordersCollection->getData();
            if (count($orders) > 0) {
                foreach ($orders as $orderData) {
                    $totalNationalDiscount = $orderData['total_discount'] - $orderData['shipping_discount'];
                    $order = $this->orderFactory->create()->loadByIncrementId($orderData['increment_id']);
                    //set national_promotional_discount on order
                    $order->setData('national_promotional_discount', $totalNationalDiscount);
                    $this->orderModel->save($order);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error when saving national promotional discount for orders: " . $e);
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
