<?php
/**
 * @package     Infosys/AemBase
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AemBase\Rewrite\Magento\Sitemap\Model;

use Infosys\AemBase\Model\AemBaseConfigProvider;
use Magento\Config\Model\Config\Reader\Source\Deployed\DocumentRoot;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Archive\Gz;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Sitemap\Helper\Data;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\ResourceModel\Catalog\CategoryFactory;
use Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory;
use Magento\Sitemap\Model\ResourceModel\Cms\PageFactory;
use Magento\Sitemap\Model\SitemapConfigReaderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customizations to include the AEM sitemap.xml in the magento sitemap index file
 * Had to override file because customizations are un-attainable with a plugin
 */
class Sitemap extends \Magento\Sitemap\Model\Sitemap
{
    /** @var AemBaseConfigProvider */
	protected AemBaseConfigProvider $configProvider;

	/** @var Gz */
	private Gz $gz;

	/**
	 * @param Context $context
	 * @param Registry $registry
	 * @param Escaper $escaper
	 * @param Data $sitemapData
	 * @param Filesystem $filesystem
	 * @param CategoryFactory $categoryFactory
	 * @param ProductFactory $productFactory
	 * @param PageFactory $cmsFactory
	 * @param DateTime\DateTime $modelDate
	 * @param StoreManagerInterface $storeManager
	 * @param RequestInterface $request
	 * @param DateTime $dateTime
	 * @param AemBaseConfigProvider $configProvider
	 * @param AbstractResource|null $resource
	 * @param AbstractDb|null $resourceCollection
	 * @param array $data
	 * @param DocumentRoot|null $documentRoot
	 * @param ItemProviderInterface|null $itemProvider
	 * @param SitemapConfigReaderInterface|null $configReader
	 * @param SitemapItemInterfaceFactory|null $sitemapItemFactory
	 * @param Gz $gz
	 */
    public function __construct(
        Context $context,
        Registry $registry,
        Escaper $escaper,
        Data $sitemapData,
        Filesystem $filesystem,
        CategoryFactory $categoryFactory,
        ProductFactory $productFactory,
        PageFactory $cmsFactory,
        DateTime\DateTime $modelDate,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        DateTime $dateTime,
        AemBaseConfigProvider $configProvider,
        Gz $gz,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DocumentRoot $documentRoot = null,
        ItemProviderInterface $itemProvider = null,
        SitemapConfigReaderInterface $configReader = null,
        SitemapItemInterfaceFactory $sitemapItemFactory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $escaper,
            $sitemapData,
            $filesystem,
            $categoryFactory,
            $productFactory,
            $cmsFactory,
            $modelDate,
            $storeManager,
            $request,
            $dateTime,
            $resource,
            $resourceCollection,
            $data,
            $documentRoot,
            $itemProvider,
            $configReader,
            $sitemapItemFactory
        );
        $this->configProvider = $configProvider;
        $this->gz = $gz;
    }

    /**
     * Customized to always create a sitemap index file so we can include the AEM sitemap xml file
     * Also creates a gzipped version of the sitemap files
     *
     * @throws LocalizedException
     */
    public function generateXml()
    {
        $this->_initSitemapItems();

        /** @var $item SitemapItemInterface */
        foreach ($this->_sitemapItems as $item) {
            $xml = $this->_getSitemapRow(
                $item->getUrl(),
                $item->getUpdatedAt(),
                $item->getChangeFrequency(),
                $item->getPriority(),
                $item->getImages()
            );

            if ($this->_isSplitRequired($xml) && $this->_sitemapIncrement > 0) {
                $this->_finalizeSitemap();

	            // Write out a gzipped copy of the file alongside the generated sitemap file
	            $this->gzipFinalizedSitemap();
            }

            if (!$this->_fileSize) {
                $this->_createSitemap();
            }

            $this->_writeSitemapRow($xml);
            // Increase counters
            $this->_lineCount++;
            $this->_fileSize += strlen($xml);
        }

        $this->_finalizeSitemap();

        // Write out a gzipped copy of the file alongside the generated sitemap file
        $this->gzipFinalizedSitemap();

        /*
         * Customizations here.
         * Removed check for $this->_sitemapIncrement == 1
         * Always create sitemap index files
         */
        $this->_createSitemapIndex();

        $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }

    /**
     * Customized to add in AEM sitemap to sitemap index
     * @throws LocalizedException
     */
    protected function _createSitemapIndex()
    {
        $this->_createSitemap($this->getSitemapFilename(), self::TYPE_INDEX);
        for ($i = 1; $i <= $this->_sitemapIncrement; $i++) {
            $xml = $this->_getSitemapIndexRow(
            	$this->_getCurrentSitemapFilename($i) . '.gz', // Provide link to gzipped file
	            $this->_getCurrentDateTime()

            );
            $this->_writeSitemapRow($xml);
        }
        //add AEM sitemap file
        if ($this->configProvider->getAemIncludeSitemapIndex() == 1) {
            $xml = $this->_getAemSitemapIndexRow(
                $this->configProvider->getAemSitemapUrl((int) $this->getStoreId()),
                $this->_getCurrentDateTime()
            );
            $this->_writeSitemapRow($xml);
        }

        $this->_finalizeSitemap(self::TYPE_INDEX);
    }

    /**
     * Slight modification of _getSitemapIndexRow to take in full url.
     *
     * @param $sitemapUrl
     * @param null $lastmod
     * @return string
     */
    protected function _getAemSitemapIndexRow($sitemapUrl, $lastmod = null)
    {
        $row = '<loc>' . $this->_escaper->escapeUrl($sitemapUrl) . '</loc>';
        if ($lastmod) {
            $row .= '<lastmod>' . $this->_getFormattedLastmodDate($lastmod) . '</lastmod>';
        }

        return '<sitemap>' . $row . '</sitemap>';
    }

    /**
     * @param string $sitemapPath
     * @param string $sitemapFileName
     * @return string
     *
     * When running from the admin to generate the sitemaps, the admin domain was getting added.
     */
    public function getSitemapUrl($sitemapPath, $sitemapFileName)
    {
        $domain = $this->configProvider->getAemDomain((int) $this->getStoreId());
        $path = $this->configProvider->getMagentoPath() . $sitemapPath . '/' . $sitemapFileName;

        $url =  $domain . $path;
	    // Use lookbehind to replace al double slashes not in the protocol
        $url = preg_replace('#(?<!:)/+#im', '/', $url);
	    $url = str_replace('/pub', '', $url);

        return $url;
    }

	/**
	 * Get url
	 *
	 * @param string $url
	 * @param string $type
	 * @return string
	 */
	protected function _getUrl($url, $type = UrlInterface::URL_TYPE_LINK)
	{
		$domain = $this->configProvider->getAemDomain((int) $this->getStoreId());

		return $domain . ltrim($url, '/');
	}

	/**
	 * @throws ValidatorException
	 */
	protected function gzipFinalizedSitemap(): void
	{
		$filePath = $this->_directory->getAbsolutePath(
			$this->getSitemapPath() . $this->_getCurrentSitemapFilename($this->_sitemapIncrement)
		);

		$gzipFilePath = $filePath . '.gz';

		$this->gz->pack(
			$filePath,
			$gzipFilePath
		);
	}
}
