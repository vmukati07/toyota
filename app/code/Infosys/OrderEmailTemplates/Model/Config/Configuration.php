<?php

/**
 * @package     Infosys/OrderEmailTemplates
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\OrderEmailTemplates\Model\Config;

use Magento\Store\Model\ScopeInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Infosys\OrderEmailTemplates\Logger\OrderEmailLogger;

/**
 * Configurations for Order Email Templates
 */
class Configuration
{
    protected ScopeConfigInterface $scopeConfig;

    protected CountryFactory $countryFactory;

    protected RegionFactory $regionFactory;
	
	protected OrderEmailLogger $logger;
    
    /**
     * Constructor function
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CountryFactory $countryFactory
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CountryFactory $countryFactory,
		OrderEmailLogger $logger,
        RegionFactory $regionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->countryFactory = $countryFactory;
		$this->logger = $logger;
        $this->regionFactory = $regionFactory;
    }

    /**
     * Get region name
     *
     * @param string $storeId
     * @return string
     */
    public function getStoreRegion($storeId) : ?string
    {
        $regionName = "";
        $regionId = $this->getConfig('general/store_information/region_id', $storeId);

        if ($regionId) {
            $region = $this->regionFactory->create()->load($regionId);
            $regionName = $region->getName();
        }

        return $regionName;
    }

    /**
     * Get country name
     *
     * @param string $storeId
     * @return string
     */
    public function getStoreCountry($storeId) : ?string
    {
        $countryName = "";
        $countryCode = $this->getConfig('general/store_information/country_id', $storeId);
        
        if ($countryCode) {
            $country = $this->countryFactory->create()->loadByCode($countryCode);
            $countryName = $country->getName();
        }

        return $countryName;
    }

    /**
     * Get AEM Customer Account URL Path
     *
     * @param string $storeId
     * @return string
     */
    public function getCustomerAccountUrl($storeId): string
    {
        $aemPath = $this->getConfig('aem_general_config/general/aem_path', $storeId);
        $customerAccount = $this->scopeConfig->getValue(
            'aem_general_config/general/aem_customer_account_path',
            ScopeInterface::SCOPE_STORE
        );
        $aemUrl = $aemPath . $customerAccount;
        
        return $aemUrl;
    }

    /**
     * Get Dealer Email
     *
     * @param string $storeId
     * @return string
     */
    public function getDealerEmail($storeId) : ?string
    {
		$this->logger->info("Store Dealer Email: ".$this->getConfig('general/store_information/store_email', $storeId));
        return $this->getConfig('general/store_information/store_email', $storeId);
    }

    /**
     * Get Dealer Phone
     *
     * @param string $storeId
     * @return string
     */
    public function getDealerPhone($storeId) : ?string
    {
        return $this->getConfig('general/store_information/phone', $storeId);
    }

    /**
     * Get Dealer Address
     *
     * @param string $storeId
     * @return string
     */
    public function getDealerAddress($storeId) : ?string
    {
        $name = $this->getConfig('general/store_information/name', $storeId);
        $street_line = $this->getConfig('general/store_information/street_line1', $storeId);
        $city = $this->getConfig('general/store_information/city', $storeId);
        $postcode = $this->getConfig('general/store_information/postcode', $storeId);
    
        $region = $this->getStoreRegion($storeId);
        $country = $this->getStoreCountry($storeId);

        $address = "";

        if ($name) {
            $address.= $name . "<br>";
        }

        if ($street_line) {
            $address.= $street_line . "<br>";
        }

        if ($city) {
            $address.= $city . "<br>";
        }

        if ($postcode) {
            $address.= $postcode . "<br>";
        }

        if ($region) {
            $address.= $region . "<br>";
        }

        if ($country) {
            $address.= $country . "<br>";
        }

        return $address;
    }

    /**
     * Get Config Data
     *
     * @param string $path
     * @param string $storeId
     * @return string
     */
    public function getConfig($path, $storeId) : ?string
    {
        $config = $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $config;
    }
}
