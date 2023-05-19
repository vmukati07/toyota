<?php
/**
 * @package Infosys/XtentoProductExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright � 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Registry;
use Infosys\XtentoProductExport\Model\CommonMethods;
use Infosys\XtentoProductExport\Logger\ProductExportLogger;

/**
 * Class to add custom variables in google feed file name
 */
class FilenameCustomVariables implements ObserverInterface
{
    protected Registry $registry;

    protected CommonMethods $helper;

    protected ProductExportLogger $logger;

    /**
     * Initialize dependencies
     *
     * @param Registry $registry
     * @param CommonMethods $helper
     * @param ProductExportLogger $logger
     *
     * @return void
     */
    public function __construct(
        Registry $registry,
        CommonMethods $helper,
        ProductExportLogger $logger
    ) {
        $this->_registry = $registry;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Method to set custom variables in file name
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer) : void
    {
        try {
            $transport = $observer->getTransport();
            $storeId = $this->_registry->registry('productexport_profile')->getStoreId();

            if ($storeId) {
                //set default value for dealer code if empty for any store
                $dealerCode = $this->helper->getDealerCode($storeId);
                $dealerCode = !empty($dealerCode) ? $dealerCode : 'default';
            
                //set custom variables
                $variables = [
                    '/%dealercode%/' => $dealerCode
                ];
                $transport->setCustomVariables($variables);
            }
        } catch (\Exception $e) {
            $this->logger->error("Error in adding dealer code in the file name for xtento products export " . $e);
        }
    }
}
