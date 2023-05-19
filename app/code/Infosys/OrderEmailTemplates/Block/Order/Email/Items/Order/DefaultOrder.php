<?php

/**
 * @package   Infosys/OrderEmailTemplates
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\OrderEmailTemplates\Block\Order\Email\Items\Order;

use Magento\Sales\Model\Order\Item as OrderItem;
use Infosys\OrderEmailTemplates\Logger\OrderEmailLogger;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class to get ordered item part number in order confirmation emails
 */
class DefaultOrder extends \Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder
{
    /**
     * @var OrderEmailLogger
     */
    protected OrderEmailLogger $logger;

    /**
     * Initialize dependencies
     *
     * @param Context $context
     * @param OrderEmailLogger $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderEmailLogger $logger,
        array $data = []
    ) {
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Returns Product Part Number for Item provided
     *
     * @param OrderItem $item
     * @return string
     */
    public function getPartNumber($item): ?string
    {
        $partNumber = $item->getSku();
        try {
            $product = $item->getProduct();
            if ($product != null && $product->getPartNumber()) {
                $partNumber = $product->getPartNumber();
            }
        } catch (\Exception $e) {
            $this->logger->error(__("Error in getting item part number while sending order confirmation email"));
            $this->logger->error($e);
            return $partNumber;
        }
        return $partNumber;
    }
}
