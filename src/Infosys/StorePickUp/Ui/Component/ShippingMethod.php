<?php

/**
 * @package     Infosys/StorePickUp
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */
declare(strict_types=1);

namespace Infosys\StorePickUp\Ui\Component;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ShippingMethod
 * 
 * Class to prepare data source of Shippimg Method column for the backend order listing grid 
 */
class ShippingMethod extends Column
{

    /**
     * @var OrderRepositoryInterface
    */
    protected $orderRepository;
    /**
     * @var scopeConfig
     */
    protected $scopeConfig; 

    /**
     * Constructor
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
        ScopeConfigInterface $scopeConfig,
        array $components = [], array $data = []
        )
    {
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Method to get data for the shpping method column
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
        $carrierTitle = '';
        foreach ($dataSource['data']['items'] as & $item) {
            $order = $this->orderRepository->get($item['entity_id']);
            $shippingCode = explode("_",$order->getShippingMethod());
            if(isset($shippingCode[0])){
                $carrierTitle = $this->scopeConfig->getValue('carriers/'.$shippingCode[0].'/title');
            }
            $item[$this->getData('name')] = $carrierTitle;
        }
        return $dataSource;
    }
}
