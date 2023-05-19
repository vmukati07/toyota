<?php

/**
 * @package     Infosys/OrderView
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\OrderView\Block\Tracking;

use Magento\Store\Model\ScopeInterface;

class Popup extends \Magento\Shipping\Block\Tracking\Popup
{
    /**
     * Method to get shipment tracking link
     *
     * @param string $carrier
     * @param string $number
     * @return string|null
     */
    public function getShipmentTrackingLink($carrier, $number): ?string
    {
        $tracking = $this->_scopeConfig->getValue(
            'shipment_tracking_config/shipment_tracking_links/' . $carrier,
            ScopeInterface::SCOPE_STORE
        );
        return $tracking . $number;
    }
}
