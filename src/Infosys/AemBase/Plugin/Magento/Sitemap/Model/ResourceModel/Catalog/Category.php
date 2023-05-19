<?php
/**
 * @package     Infosys/AemBase
 * @version     1.0.0
 * @author      Infosys Limited
 * @copyright   Copyright © 2021. All Rights Reserved.
 */

declare(strict_types=1);

namespace Infosys\AemBase\Plugin\Magento\Sitemap\Model\ResourceModel\Catalog;

use Infosys\AemBase\Model\AemBaseConfigProvider;
use Infosys\AemBase\Model\HtmlExtensionRemover;

/**
 * Plugin responsible for altering the sitemap URLs for AEM for Categories
 */
class Category
{
    /** @var AemBaseConfigProvider */
	protected AemBaseConfigProvider $configProvider;

	/** @var HtmlExtensionRemover */
	private HtmlExtensionRemover $htmlExtensionRemover;

    /** @var int */
    private int $storeId;

	/**
	 * @param AemBaseConfigProvider $configProvider
	 * @param HtmlExtensionRemover $htmlExtensionRemover
	 */
    public function __construct(
        AemBaseConfigProvider $configProvider,
        HtmlExtensionRemover $htmlExtensionRemover
    ) {
        $this->configProvider = $configProvider;
        $this->htmlExtensionRemover = $htmlExtensionRemover;
    }

	/**
	 * Cache the $storeId getCollection was called with for use in afterGetCollection
	 *
	 * @param \Magento\Sitemap\Model\ResourceModel\Catalog\Category $subject
	 * @param $storeId
	 * @return array
	 */
    public function beforeGetCollection(
	    \Magento\Sitemap\Model\ResourceModel\Catalog\Category $subject,
		$storeId
    ) {
    	$this->storeId = (int) $storeId;

    	return [$storeId];
    }

	/**
	 * Replace the URLs in items in the collection with the AEM path for Categories
	 *
	 * @param \Magento\Sitemap\Model\ResourceModel\Catalog\Category $subject
	 * @param $result
	 * @return mixed
	 */
    public function afterGetCollection(
        \Magento\Sitemap\Model\ResourceModel\Catalog\Category $subject,
        $result
    ) {
	    $aemCategoryPath = $this->configProvider->getAemCategoryPath($this->storeId);

        foreach ($result as $category) {
	        // Strip off all category path parts except the final one
	        $categoryPath = $category->getData('url');
	        $parts = explode('/', $categoryPath);
	        $categoryPath = end($parts);

	        $categoryPath = $aemCategoryPath . $this->htmlExtensionRemover->execute($categoryPath);

	        $category->setData('url', $categoryPath);
        }

        return $result;
    }
}
