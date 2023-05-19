<?php

/**
 * @package     Infosys/OrderAttribute
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\OrderAttribute\Model\Resolver\Order;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * ShippingInfo class
 *
 * Infosys\OrderAttribute\Model\Resolver\Order
 */
class ShippingInfo implements ResolverInterface
{
    /**
     * Get order website name
     *
     * @param object $field
     * @param object $context
     * @param object $info
     * @param array $value
     * @param array $args
     * @return void
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $order = $value['model'];

        $carrierCode = null;
        if ($order->getShippingMethod()) {
            $carrierCode = explode('_', $order->getShippingMethod());
            if (isset($carrierCode[0])) {
                $carrierCode = $carrierCode[0];
            }
        }

        $shippingInfoArray = [];
        if ($order->getId()) {
            $shippingInfoArray = [
                'shipping_method' => $order->getShippingMethod(),
                'shipping_description' => $order->getShippingDescription(),
                'carrier_code' => $carrierCode,
            ];
        }

        return $shippingInfoArray;
    }
}
