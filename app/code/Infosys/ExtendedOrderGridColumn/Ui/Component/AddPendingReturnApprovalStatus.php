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
use \Magento\Rma\Model\ResourceModel\Item\CollectionFactory as RmaItem;
use \Magento\Sales\Api\OrderRepositoryInterface;

/**
 * AddPendingReturnApprovalStatus class
 *
 * Infosys\ExtendedOrderGridColumn\Ui\Component
 */
class AddPendingReturnApprovalStatus extends Column
{
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $_orderRepository;

    /**
     * @var RmaItem
     */
    protected RmaItem $rmaItem;

    /**
     * Constructor function For Return Approval Status
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param RmaItem $rmaItem
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderRepositoryInterface $orderRepository,
        RmaItem $rmaItem,
        array $components = [],
        array $data = []
    ) {
        $this->_orderRepository = $orderRepository;
        $this->rmaItem = $rmaItem;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    /**
     * Return Approval Status prepareDataSource
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
            if (isset($item["entity_id"]) && $this->getData('name') == 'pending_return_approval_status') {
                $order = $this->_orderRepository->get($item["entity_id"]);
                $pendingReturnedItems = 0;
                foreach ($order->getAllItems() as $items) {
                    if ($items->getQtyReturned() <= 0) {
                        $rmaItemCollection = $this->rmaItem->create()
                            ->addFieldToFilter(
                                'order_item_id',
                                [
                                    'eq' => $items->getItemId(),
                                ]
                            )->addFieldToFilter(
                                'status',
                                [
                                    'nin' => ['approved', 'denied', 'rejected']
                                ]
                            );
                        if ($rmaItemCollection->getData()) {
                            foreach ($rmaItemCollection as $rma) {
                                $pendingReturnedItems += $rma->getQtyRequested();
                            }
                        }
                    }
                }
                $item[$this->getData('name')] = $pendingReturnedItems . " of " . (int)$order->getTotalQtyOrdered();
            }
        }

        return $dataSource;
    }
}
