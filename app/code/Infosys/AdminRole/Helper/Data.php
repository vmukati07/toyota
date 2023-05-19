<?php

/**
 * @package     Infosys/AdminRole
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AdminRole\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;

/**
 * This class provides helper functions to translate incoming Dealer and Region codes to Website IDs.
 */
class Data extends AbstractHelper
{

    /**
     *
     * @var CollectionFactory
     */
    private $websiteCollection;

    /**
     * Constructor function
     *
     * @param Context $context
     * @param CollectionFactory $websiteCollection
     */
    public function __construct(
        Context $context,
        CollectionFactory $websiteCollection
    ) {
        parent::__construct($context);
        $this->websiteCollection = $websiteCollection;
    }

    /**
     * Get Dealer Website Id based on 5 digit dealer code.
     *
     * @param string $dealerCode
     * @return array
     */
    public function getDealerWebsite(string $dealerCode) : array
    {
        $websiteCollection = $this->websiteCollection->create();
        $websiteCollection->addFieldToFilter('dealer_code', ['eq' => $dealerCode]);
        return $websiteCollection->getAllIds();
    }

    /**
     * Get all website ids based on Corporate Region Code
     *
     * @param string $regionCode
     * @return array
     */
    public function getRegionalWebsite(string $regionCode) : array
    {
        $websiteCollection = $this->websiteCollection->create();
        $websiteCollection->join('toyota_dealer_regions', 'main_table.region_id = toyota_dealer_regions.id')->
        addFieldToFilter('region_code', ['eq' => $regionCode]);
        return $websiteCollection->getAllIds();
    }

    /**
     * Lookup Website IDs based on input region and dealer code
     * @param string|null $regionCode
     * @param string|null $dealerCode
     * @return array
     * @throws AuthenticationException
     */
    public function calculateWebsiteIds(string $regionCode = null, string $dealerCode = null) : array
    {
        $regionWebsites = [];
        $dealerWebsites = [];

        if ($regionCode) {
            $regionWebsites = $this->getRegionalWebsite($regionCode);
        }

        if ($dealerCode) {
            $dealerWebsites = $this->getDealerWebsite($dealerCode);
        }

        return array_merge($regionWebsites, $dealerWebsites);
    }

}
