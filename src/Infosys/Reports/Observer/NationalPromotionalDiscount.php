<?php

/**
 * @package     Infosys/Reports
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\Reports\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use Magento\SalesRule\Model\RuleFactory;
use Psr\Log\LoggerInterface;

/**
 * Class to save national promotional discount on orders
 */
class NationalPromotionalDiscount implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var RuleFactory
     */
    protected RuleFactory $ruleFactory;

    /**
     * Initialize dependencies
     *
     * @param LoggerInterface $logger
     * @param RuleFactory $ruleFactory
     */
    public function __construct(
        LoggerInterface $logger,
        RuleFactory $ruleFactory
    ) {
        $this->logger = $logger;
        $this->ruleFactory = $ruleFactory;
    }

    /**
     * Observer to save national promotional discount on orders
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getDataByKey('quote');
        $order = $observer->getEvent()->getDataByKey('order');
        try {
            $totalNationalDiscount = 0;
            $address = $quote->getShippingAddress();
            $totalDiscounts = $address->getExtensionAttributes()->getDiscounts();
            if ($totalDiscounts && is_array($totalDiscounts)) {
                foreach ($totalDiscounts as $value) {
                    $ruleId = $value->getRuleId();
                    $discountData = $value->getDiscountData();
                    $ruleInfo = $this->ruleFactory->create()->load($ruleId);
                    $websiteIds = (array)$ruleInfo->getWebsiteIds();
                    if (count($websiteIds) > 1) {
                        $totalNationalDiscount += $discountData->getAmount();
                    }
                }
            }
            //set national_promotional_discount on order
            $order->setNationalPromotionalDiscount($totalNationalDiscount);
        } catch (\Exception $e) {
            $this->logger->error(__("Error when saving national promotional discount: %1", $e));
        }
    }
}
