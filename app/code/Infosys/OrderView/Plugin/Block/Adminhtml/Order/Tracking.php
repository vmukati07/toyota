<?php

/**
 * @package     Infosys/OrderView
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\OrderView\Plugin\Block\Adminhtml\Order;

use Magento\Shipping\Block\Adminhtml\Order\Tracking as CoreTracking;

/**
 * Class to remove shipstation carrier in the result
 */
class Tracking
{
    /**
     * Funtion to remove shipstation carrier in the result
     *
     * @param CoreTracking $subject
     * @param array $result
     * @return array
     */
    public function aftergetCarriers(
        CoreTracking $subject,
        $result
    ) {
        $carriers = [];
        if (!empty($result)) {
            foreach ($result as $code => $title) {
                if ($code == 'shipstation') {
                    continue;
                }
                $carriers[$code] = $title;
            }
            $result = $carriers;
        }
        return $result;
    }
}
