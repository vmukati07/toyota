<?php

/**
 * @package     Infosys/ExtendedOrderGridColumn
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare (strict_types = 1);

namespace Infosys\ExtendedOrderGridColumn\Ui\Component;

use Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Sales\Api\OrderRepositoryInterface;

/**
 * AddPendingDealerShipmentStatus class
 *
 * Infosys\ExtendedOrderGridColumn\Ui\Component
 */
class AddPendingDealerShipmentStatus extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $_orderRepository;

    /**
     * Constructor function For Pending Dealer Shipment Status
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    /**
     * Pending Dealer Shipment Status prepareDataSource
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        parent::prepareDataSource($dataSource);

        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (isset($item["entity_id"]) && $this->getData('name') == 'pending_dealer_shipment_status') {
                $order = $this->_orderRepository->get($item["entity_id"]);
                $shipped = 0;
                $canceled = 0;
                foreach ($order->getAllItems() as $items) {
                    if ($items->getQtyCanceled() != 0) {
                        $canceled += $items->getQtyCanceled();
                    }
                    if ($items->getQtyShipped() != 0) {
                        $shipped += $items->getQtyShipped();
                    }
                }
                $pendingShipped = (int)$order->getTotalQtyOrdered() - $shipped - $canceled;
                $item[$this->getData('name')] = $pendingShipped . " of " . (int)$order->getTotalQtyOrdered();
            }
        }

        return $dataSource;
    }
}
