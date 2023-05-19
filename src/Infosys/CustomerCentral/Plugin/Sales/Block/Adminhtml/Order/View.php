<?php
/**
 * @package     Infosys/CustomerCentral
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright 2022. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\CustomerCentral\Plugin\Sales\Block\Adminhtml\Order;

use Infosys\CustomerCentral\Model\GetCustomerCentralOrderApiStatus;
use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

/**
 * Responsible for adding the "Customer Central" button on the Order View screen if requirements are met
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
     * Before setLayout, if the customer needs to be sync'd, present a button to execute that
	 * If the order needs to be sync'd, present a button to execute that
     *
     * @param OrderView $subject
     * @return void
     */
    public function beforeSetLayout(OrderView $subject)
    {
	    $order = $subject->getOrder();

	    // We don't want to send Orders to Customer Central if they are still awaiting fraud approval (Signifyd)
	    // Don't display any buttons if the order is in review, (state is holded)
	    if ($order->getState() === 'holded') {
	    	return;
	    }

    	$params = [];
    	$label = 'Customer Central (Order)';

	    $orderMissingCcId = is_null($order->getCustomerCentralId());

	    if($orderMissingCcId) {
	    	$params['submit_customer'] = true;
	    	$label = 'Customer Central (Customer)';
	    }

        if ($orderMissingCcId ||
        	!$this->getCustomerCentralOrderApiStatus->execute((int) $order->getId())) {
            $subject->addButton(
                'order_customer_central_button',
                [
                    'label' => __('%1', $label),
                    'class' => __('custom-button'),
                    'id' => 'order-customer-central-button',
                    'onclick' =>
	                    'setLocation(\'' .
	                    $subject->getUrl('customer_sync/order/customercentralsubmit', $params) .
	                    '\')'
                ]
            );
        }
    }
}
