<?php

/**
 * @package   Infosys/OrderEmailTemplates
 * @version   1.0.0
 * @author    Infosys Limited
 * @copyright Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\OrderEmailTemplates\Block\Order\Email\Items;

use Magento\Sales\Model\Order\Item as OrderItem;
use Infosys\OrderEmailTemplates\Logger\OrderEmailLogger;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\ProductFactory;

/**
 * Class to get ordered item part number in invoice,shipment and creditmemo confirmation emails
 */
class DefaultItems extends \Magento\Sales\Block\Order\Email\Items\DefaultItems
{
    /**
     * @var OrderEmailLogger
     */
    protected OrderEmailLogger $logger;

    /**
     * @var ProductFactory
     */
    protected ProductFactory $productFactory;

    /**
     * Initialize dependencies
     *
     * @param Context $context
     * @param OrderEmailLogger $logger
     * @param ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderEmailLogger $logger,
        ProductFactory $productFactory,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->productFactory = $productFactory;
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
            $product_id = $item->getProductId();
            $product = $this->productFactory->create()->load($product_id);
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
