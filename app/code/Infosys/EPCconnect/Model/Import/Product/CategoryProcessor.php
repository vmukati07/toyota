<?php

/**
 * @package     Infosys/EPCconnect
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

namespace Infosys\EPCconnect\Model\Import\Product;

use Magento\Framework\Mail\Template\TransportBuilder;
use Infosys\EPCconnect\Helper\Data;
use Infosys\AemBase\Model\AemBaseConfigProvider;
use Psr\Log\LoggerInterface;

/**
 * Class CategoryProcessor
 *
 * @api
 * @since 100.0.2
 */
class CategoryProcessor extends \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor
{
    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryColFactory;

    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * Categories id to object cache.
     *
     * @var array
     */
    protected $categoriesCache = [];

    /**
     * Instance of catalog category factory.
     *
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * Failed categories during creation
     *
     * @var array
     * @since 100.1.0
     */
    protected $failedCategories = [];

    /**
     * Construct function
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param TransportBuilder $transportBuilder
     * @param Data $helperData
     * @param AemBaseConfigProvider $configProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        TransportBuilder $transportBuilder,
        Data $helperData,
        AemBaseConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->categoryColFactory = $categoryColFactory;
        $this->categoryFactory = $categoryFactory;
        $this->transportBuilder = $transportBuilder;
        $this->helperData = $helperData;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->initCategories();
    }

    /**
     * Initialize categories
     *
     * @return $this
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $collection = $this->categoryColFactory->create();
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('url_key')
                ->addAttributeToSelect('url_path');
            $collection->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            foreach ($collection as $category) {
                $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                $pathSize = count($structure);

                $this->categoriesCache[$category->getId()] = $category;
                if ($pathSize > 1) {
                    $path = [];
                    for ($i = 1; $i < $pathSize; $i++) {
                        if ($collection->getItemById($structure[$i]) == Null) { continue; }
                        $name = $collection->getItemById((int)$structure[$i])->getName();
                        $path[] = $this->quoteDelimiter($name);
                    }
                    /** @var string $index */
                    $index = $this->standardizeString(
                        implode(self::DELIMITER_CATEGORY, $path)
                    );
                    $this->categories[$index] = $category->getId();
                }
            }
        }
        return $this;
    }

    /**
     * Creates a category.
     *
     * @param string $name
     * @param int $parentId
     * @return int
     */
    protected function createCategory($name, $parentId)
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->categoryFactory->create();
        if (!($parentCategory = $this->getCategoryById($parentId))) {
            $parentCategory = $this->categoryFactory->create()->load($parentId);
        }

        $url_key = '';
        $categories = explode("/", $parentCategory->getPath());  
        $categories = array_slice($categories,2);

        foreach($categories as $categoryId){
            $cat = $this->getCategoryById($categoryId);
            $catagoryName = strtolower($cat->getName());
            $catagoryName = str_replace(" ", "-", $catagoryName);
            if($url_key != ''){
                $url_key = $url_key."-".$catagoryName;
            }
            else{
                $url_key = $catagoryName;
            }            
        }

        $currentCategoryName = strtolower($this->unquoteDelimiter($name));
        $currentCategoryName = str_replace(" ", "-", $currentCategoryName);
        
        if($url_key != ''){
            $url_key = $url_key."-".$currentCategoryName;
        }
        else{
            $url_key = $currentCategoryName;
        }
        
        $category->setPath($parentCategory->getPath());
        $category->setParentId($parentId);
        $category->setName($this->unquoteDelimiter($name));
        $category->setIsActive(true);
        $category->setIncludeInMenu(true);
        $category->setAttributeSetId($category->getDefaultAttributeSetId());
        $category->setData('url_key', $url_key);
        $category->save();

        if (!empty($category->getId())) {
            $aemCategoryPath = $this->configProvider->getAemDomain(\Magento\Store\Model\Store::DEFAULT_STORE_ID).'/'.$this->configProvider->getAemCategoryPath(\Magento\Store\Model\Store::DEFAULT_STORE_ID).$url_key;
            $this->createCategoryNotify($name, $aemCategoryPath);
        }
        
        $this->categoriesCache[$category->getId()] = $category;
        return $category->getId();
    }

    /**
     * createCategoryNotify function
     *
     * @param [type] $categoryName
     * @param [type] $aemCategoryPath
     * @return void
     */
    protected function createCategoryNotify($categoryName, $aemCategoryPath)
    {
        try {
            $senderName = $this->helperData->getConfig('trans_email/ident_general/name');
            $senderMail = $this->helperData->getConfig('trans_email/ident_general/email');
            $recipientMail = $this->helperData->getConfig('epcconnect/category_detect_during_import/recipient_email');

            $SenderDetails = ['email' => $senderMail, 'name' => $senderName];
            $recipientDetails = [$recipientMail];

            $transport = $this->transportBuilder
            ->setTemplateIdentifier('create_category_notify')
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
                ]
                )
            ->setTemplateVars([
                'category_name'  => $categoryName,
                'category_url'  => $aemCategoryPath
            ])
            ->setFromByScope($SenderDetails)
            ->addTo($recipientDetails)
            ->getTransport();
                
            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }
    
    /**
     * Returns ID of category by string path creating nonexistent ones.
     *
     * @param string $categoryPath
     * @return int
     */
    protected function upsertCategory($categoryPath)
    {
        /** @var string $index */
        $index = $this->standardizeString($categoryPath);

        if (!isset($this->categories[$index])) {
            $pathParts = preg_split('~(?<!\\\)' . preg_quote(self::DELIMITER_CATEGORY, '~') . '~', $categoryPath);
            $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
            $path = '';

            foreach ($pathParts as $pathPart) {
                $path .= $this->standardizeString($pathPart);
                if (!isset($this->categories[$path])) {
                    $this->categories[$path] = $this->createCategory($pathPart, $parentId);
                }
                $parentId = $this->categories[$path];
                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $this->categories[$index];
    }

    /**
     * Returns IDs of categories by string path creating nonexistent ones.
     *
     * @param string $categoriesString
     * @param string $categoriesSeparator
     * @return array
     */
    public function upsertCategories($categoriesString, $categoriesSeparator)
    {
        $categoriesIds = [];
        $categories = explode($categoriesSeparator, $categoriesString);

        foreach ($categories as $category) {
            try {
                $categoriesIds[] = $this->upsertCategory($category);
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                $this->addFailedCategory($category, $e);
            }
        }

        return $categoriesIds;
    }

    /**
     * Add failed category
     *
     * @param string $category
     * @param \Magento\Framework\Exception\AlreadyExistsException $exception
     *
     * @return $this
     */
    private function addFailedCategory($category, $exception)
    {
        $this->failedCategories[] =
            [
                'category' => $category,
                'exception' => $exception,
            ];
        return $this;
    }

    /**
     * Return failed categories
     *
     * @return array
     * @since 100.1.0
     */
    public function getFailedCategories()
    {
        return $this->failedCategories;
    }

    /**
     * Resets failed categories' array
     *
     * @return $this
     * @since 100.2.0
     */
    public function clearFailedCategories()
    {
        $this->failedCategories = [];
        return $this;
    }

    /**
     * Get category by Id
     *
     * @param int $categoryId
     *
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCategoryById($categoryId)
    {
        return $this->categoriesCache[$categoryId] ?? null;
    }

    /**
     * Standardize a string.
     * For now it performs only a lowercase action, this method is here to include more complex checks in the future
     * if needed.
     *
     * @param string $string
     * @return string
     */
    private function standardizeString($string)
    {
        return mb_strtolower($string);
    }

    /**
     * Quoting delimiter character in string.
     *
     * @param string $string
     * @return string
     */
    private function quoteDelimiter($string)
    {
        return str_replace(self::DELIMITER_CATEGORY, '\\' . self::DELIMITER_CATEGORY, $string);
    }

    /**
     * Remove quoting delimiter in string.
     *
     * @param string $string
     * @return string
     */
    private function unquoteDelimiter($string)
    {
        return str_replace('\\' . self::DELIMITER_CATEGORY, self::DELIMITER_CATEGORY, $string);
    }
}
