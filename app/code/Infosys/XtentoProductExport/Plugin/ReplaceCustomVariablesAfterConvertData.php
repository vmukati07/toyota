<?php
/**
 * @package Infosys/XtentoProductExport
 * @version 1.0.0
 * @author Infosys Limited
 * @copyright Copyright � 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Plugin;

use Magento\Framework\Registry;
use Infosys\XtentoProductExport\Model\CommonMethods;
use Infosys\XtentoProductExport\Logger\ProductExportLogger;

/**
 * Plugin to replace custom variables in output format for google feed
 */
class ReplaceCustomVariablesAfterConvertData
{
    /**
     * @var Registry
     */
    protected Registry $registry;

    /**
     * @var CommonMethods
     */
    protected CommonMethods $helper;

    /**
     * @var ProductExportLogger
     */
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
        $this->registry = $registry;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    /**
     * Replace custom variables in output format
     *
     * @param \Xtento\ProductExport\Model\Output\Xsl $subject
     * @param array $result
     * @return array
     */
    public function afterConvertData(\Xtento\ProductExport\Model\Output\Xsl $subject, $result)
    {
        try {
            $storeId = (int) $this->registry->registry('productexport_profile')->getStoreId();

            if ($storeId) {
                $storeName = $this->helper->getStoreName($storeId);
                $storeLink = $this->helper->getStoreLink($storeId);

                $replaceableVariables = [
                    '/%title%/' => $storeName . " data feed",
                    '/%link%/' => $storeLink,
                    '/%description%/' => "Google data feed for " . $storeName
                ];

                if ($result) {
                    foreach ($result as $filename => $outputData) {
                        $result[$filename] = preg_replace(
                            array_keys($replaceableVariables),
                            array_values($replaceableVariables),
                            $outputData
                        );
                    }
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $message = "Error in replacing custom variables in the output format for xtento products export ";
            $this->logger->error($message . $e);
        }
    }
}
