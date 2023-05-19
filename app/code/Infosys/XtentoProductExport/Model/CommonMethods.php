<?php

/**
 * @package     Infosys/XtentoProductExport
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\XtentoProductExport\Model;

use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Infosys\AemBase\Model\AemBaseConfigProvider;
use Infosys\XtentoProductExport\Logger\ProductExportLogger;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory;

/**
 * Common methods for xtento product export
 */
class CommonMethods
{
    public const XTENTO_EXPORT_PROFILE = "xtento_productexport_profile";
    
    /**
     * @var StoreRepositoryInterface
     */
    private StoreRepositoryInterface $storeRepository;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * @var AemBaseConfigProvider
     */
    protected AemBaseConfigProvider $aemBaseConfigProvider;

    /**
     * @var ProductExportLogger
     */
    protected ProductExportLogger $logger;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $storeCollection;

    /**
     * Initialize dependencies
     *
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resource
     * @param AemBaseConfigProvider $aemBaseConfigProvider
     * @param ProductExportLogger $logger
     * @param CollectionFactory $storeCollection
     *
     * @return void
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource,
        AemBaseConfigProvider $aemBaseConfigProvider,
        ProductExportLogger $logger,
        CollectionFactory $storeCollection
    ) {
        $this->storeRepository = $storeRepository;
        $this->storeManager = $storeManager;
        $this->_connection = $resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $this->aemBaseConfigProvider = $aemBaseConfigProvider;
        $this->logger = $logger;
        $this->storeCollection = $storeCollection;
    }

    /**
     * Get all stores
     *
     * @return array
     */
    public function getAllStores() : ?array
    {
        $storeList = $this->storeRepository->getList();
        return $storeList;
    }

    /**
     * Get dealer code by store id
     *
     * @param integer $storeId
     * @return string|null
     */
    public function getDealerCode($storeId) : ?string
    {
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $dealerCode = $this->storeManager->getWebsite($websiteId)->getDealerCode();
        
        return $dealerCode;
    }

    /**
     * Get store name by store id
     *
     * @param integer $storeId
     * @return string|null
     */
    public function getStoreName($storeId) : ?string
    {
        $storeName = $this->storeManager->getStore($storeId)->getName();
        
        return $storeName;
    }

    /**
     * Get store link by store id
     *
     * @param integer $storeId
     * @return string|null
     */
    public function getStoreLink($storeId) : ?string
    {
        $storeLink = $this->aemBaseConfigProvider->getAemDomain($storeId);
        
        return $storeLink;
    }

    /**
     * Get all stores for which profile not exist
     *
     * @return array
     */
    public function getStoresProfileNotExist() : ?array
    {
        $storeCollection = $this->storeCollection->create();
        $storeCollection->getSelect()->joinLeft(
            ['profiles' => self::XTENTO_EXPORT_PROFILE],
            "main_table.store_id = profiles.store_id",
            ['']
        )
            ->where('profiles.store_id IS NULL')
            ->orWhere('profiles.export_filter_include_in_feed = (?)', 0)
            ->where('profiles.enabled = (?)', 1);
        $profileStores = $storeCollection->getData();
        return $profileStores;
    }

    /**
     * Get all stores for which profiles already existed
     *
     * @param integer $profileId
     * @return array
     */
    public function getExistingProfiles($profileId) : ?array
    {
        $storeCollection = $this->storeCollection->create();
        $storeCollection->getSelect()->joinLeft(
            ['profiles' => self::XTENTO_EXPORT_PROFILE],
            "main_table.store_id = profiles.store_id",
            ['profiles.profile_id']
        )
            ->where('profiles.export_filter_include_in_feed = (?)', 1)
            ->where('profiles.profile_id != (?)', $profileId)
            ->where('profiles.enabled = (?)', 1);
        $profileStores = $storeCollection->getData();
        return $profileStores;
    }

    /**
     * Get profile data by profile id
     *
     * @param integer $profileId
     * @return array|boolean
     */
    public function getProfileData($profileId)
    {
        $selectProfile = $this->_connection->select()
            ->from(
                ['profile' => self::XTENTO_EXPORT_PROFILE],
                ['*']
            )
            ->where(
                "profile.profile_id = ?",
                $profileId
            )->where('profile.enabled = (?)', 1);
        $profile = $this->_connection->fetchRow($selectProfile);
        return $profile;
    }

    /**
     * Insert profiles data
     *
     * @param array $data
     * @return void
     */
    public function insertProfilesData($data) : void
    {
        try {
            $this->_connection->insertMultiple(
                self::XTENTO_EXPORT_PROFILE,
                $data
            );
        } catch (\Exception $e) {
            $this->logger->error("Error in inserting xtento profiles data " . $e);
        }
    }

    /**
     * Update profile data
     *
     * @param array $profileData
     * @param integer $profileId
     * @return void
     */
    public function updateProfileData($profileData, $profileId) : void
    {
        try {
            $this->_connection->update(
                self::XTENTO_EXPORT_PROFILE,
                $profileData,
                ['profile_id = ?' => (int)$profileId]
            );
        } catch (\Exception $e) {
            $this->logger->error("Error in updating xtento profile data " . $e);
        }
    }
}
