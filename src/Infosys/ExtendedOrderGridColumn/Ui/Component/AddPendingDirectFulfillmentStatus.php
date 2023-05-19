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
 * AddPendingDirectFulfillmentStatus class
 *
 * Infosys\ExtendedOrderGridColumn\Ui\Component
 */
class AddPendingDirectFulfillmentStatus extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $_orderRepository;

    /**
     * Constructor function For Direct FulFillment Status
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
     * Direct FulFillment Status prepareDataSource
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
            if (isset($item["entity_id"]) && $this->getData('name') == 'pending_direct_fulfillment_status') {
                $order = $this->_orderRepository->get($item["entity_id"]);
                $directFulfillmentItem = $pendingDirectFulfillmentItem = $shippedQty = 0;
                if ($order->getStatus() != 'canceled') {
                    foreach ($order->getAllItems() as $items) {
                        if ($items->getDirectFulfillmentStatus() &&
                            !str_contains(strtoupper($items->getDirectFulfillmentStatus()), 'REJECTED') &&
                            !str_contains(strtoupper($items->getDirectFulfillmentStatus()), 'CANCELLED') &&
                            !str_contains(strtoupper($items->getDirectFulfillmentStatus()), 'APPROVED')
                        ) {
                            $directFulfillmentItem += $items->getQtyOrdered();
                            $shippedQty += $items->getQtyShipped();
                        }
                    }
                }
                $pendingDirectFulfillmentItem = $directFulfillmentItem - $shippedQty;
                $item[$this->getData('name')] = $pendingDirectFulfillmentItem
                    . " of " . (int) $order->getTotalQtyOrdered();
            }
        }

        return $dataSource;
    }
}
