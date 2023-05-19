<?php
/**
 * @package     Infosys/DirectFulFillment
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright � 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\DirectFulFillment\Plugin\Sales\Block\Adminhtml\Order;

use Infosys\CustomerCentral\Model\GetCustomerCentralOrderApiStatus;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

/**
 * Responsible for adding the "Direct Fulfillment" button on the Order View screen if requirements are met
 */
class View
{
	/** @var GetCustomerCentralOrderApiStatus */
	private GetCustomerCentralOrderApiStatus $getCustomerCentralOrderApiStatus;

	/**
	 * @param GetCustomerCentralOrderApiStatus $getCustomerCentralOrderApiStatus
	 */
	public function __construct(
		GetCustomerCentralOrderApiStatus $getCustomerCentralOrderApiStatus
	) {
		$this->getCustomerCentralOrderApiStatus = $getCustomerCentralOrderApiStatus;
	}

	/**
     * Before setLayout, add "Direct Fulfillment" if requirements are met
     *
     * @param OrderView $subject
     * @return void
     */
    public function beforeSetLayout(OrderView $subject)
    {
        $order = $subject->getOrder();

        if ($this->getCustomerCentralOrderApiStatus->execute((int) $subject->getOrderId()) &&
        	$order->canShip() &&
	        !$order->getForcedShipmentWithInvoice() &&
	        $order->getDirectFulfillmentStatus() &&
	        $order->getCustomerCentralId() &&
	        !$this->checkAlreadySent($order)) {
            $subject->addButton(
                'order_direct_fulfillment_button',
                [
                    'label' => __('Direct Fulfillment'),
                    'class' => __('custom-button'),
                    'id' => 'order-direct-fulfillment-button',
                    'onclick' => 'setLocation(\'' . $subject->getUrl('direct/fulfillment/index') . '\')'
                ]
            );
        }
    }

    /**
     * Check Order submitted to DDOA
     *
     * @param mixed $order
     * @return mixed
     */
    public function checkAlreadySent($order)
    {
        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getDealerDirectFulfillmentStatus() == 1) {
                return true;
            }
        }

        return false;
    }
}
