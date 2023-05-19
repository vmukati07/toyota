<?php

/**
 * @package     Infosys/CustomerWebsiteOrders
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\CustomerWebsiteOrders\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesGraphQL\Model\Order\OrderAddress as CoreOrderAddress;

/**
 * Class to get the order address details
 */
class OrderAddress
{
    /**
     * Get the order Shipping address
     *
     * @param CoreOrderAddress $subject
     * @param array|null $result
     * @param OrderInterface $order
     * @return array|null
     */
    public function afterGetOrderShippingAddress(
        CoreOrderAddress $subject,
        $result,
        OrderInterface $order
    ): ?array {
        if (is_array($result)) {
            $region_code = $order->getShippingAddress()->getRegionCode();
            $result['region_id'] = $region_code;
        }
        return $result;
    }

    /**
     * Get the order billing address
     *
     * @param CoreOrderAddress $subject
     * @param array|null $result
     * @param OrderInterface $order
     * @return array|null
     */
    public function afterGetOrderBillingAddress(
        CoreOrderAddress $subject,
        $result,
        OrderInterface $order
    ): ?array {
        if (is_array($result)) {
            $region_code = $order->getBillingAddress()->getRegionCode();
            $result['region_id'] = $region_code;
        }
        return $result;
    }
}
